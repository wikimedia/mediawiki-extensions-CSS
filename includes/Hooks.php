<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @author Rusty Burchfield
 * @copyright © 2007-2010 Aran Dunkley
 * @copyright © 2011 Rusty Burchfield
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\CSS;

use Config;
use MediaWiki\Extension\CSS\Hooks\HookRunner;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\RawPageViewBeforeOutputHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Parser;
use RawAction;
use Wikimedia\CSS\Parser\Parser as CSSParser;
use Wikimedia\CSS\Sanitizer\FontFaceAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\KeyframesAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\MediaAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\NamespaceAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\PageAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\StylePropertySanitizer;
use Wikimedia\CSS\Sanitizer\StyleRuleSanitizer;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;
use Wikimedia\CSS\Sanitizer\SupportsAtRuleSanitizer;
use Wikimedia\CSS\Util as CSSUtil;

class Hooks implements ParserFirstCallInitHook, RawPageViewBeforeOutputHook {

	private static ?StylesheetSanitizer $sanitizer = null;

	private Config $config;
	private HookContainer $hookContainer;

	public function __construct(
		Config $config,
		HookContainer $hookContainer
	) {
		$this->config = $config;
		$this->hookContainer = $hookContainer;
	}

	private function getSanitizer(): StylesheetSanitizer {
		// This function is based on TemplateStyles's Hooks::getSanitizer():
		// https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+/refs/heads/master/includes/Hooks.php
		if ( !self::$sanitizer ) {
			$matcherFactory = new CSSMatcherFactory;

			$propertySanitizer = new StylePropertySanitizer( $matcherFactory );
			$hookRunner = new HookRunner( $this->hookContainer );
			$hookRunner->onCSSPropertySanitizer( $propertySanitizer, $matcherFactory );

			$ruleSanitizers = [
				'style' => new StyleRuleSanitizer( $matcherFactory->cssSelectorList(), $propertySanitizer ),
				'@font-face' => new FontFaceAtRuleSanitizer( $matcherFactory ),
				'@keyframes' => new KeyframesAtRuleSanitizer( $matcherFactory, $propertySanitizer ),
				'@page' => new PageAtRuleSanitizer( $matcherFactory, $propertySanitizer ),
				'@media' => new MediaAtRuleSanitizer( $matcherFactory->cssMediaQueryList() ),
				'@supports' => new SupportsAtRuleSanitizer( $matcherFactory, [
					'declarationSanitizer' => $propertySanitizer,
				] ),

				// Do not include @import due to lack of proper security measures
				'@namespace' => new NamespaceAtRuleSanitizer( $matcherFactory ),
			];

			$ruleSanitizers['@media']->setRuleSanitizers( $ruleSanitizers );
			$ruleSanitizers['@supports']->setRuleSanitizers( $ruleSanitizers );

			self::$sanitizer = new StylesheetSanitizer( $ruleSanitizers );
			$hookRunner->onCSSStylesheetSanitizer( self::$sanitizer, $propertySanitizer, $matcherFactory );
		}
		return self::$sanitizer;
	}

	/**
	 * Sanitize the provided css
	 */
	private function sanitizeCSS( string $css ): string {
		// Errors are reported vaguely since the previous implementation was also vague, and since doing
		// so could help avoid an amplification DoS (T368594#10146978). This can be revisited though.
		// This also fails silently rather than loudly, supposedly partly for consistency with the former
		// implementation, but actually mainly due to laziness. This can also be revisited.

		$cssParser = CSSParser::newFromString( $css );
		$css = $cssParser->parseStylesheet();
		if ( $cssParser->getParseErrors() ) {
			return '/* css-sanitizer failed to parse CSS */';
		}

		$sanitizer = $this->getSanitizer();
		$sanitizer->clearSanitizationErrors();
		$css = $sanitizer->sanitize( $css );
		if ( $sanitizer->getSanitizationErrors() ) {
			return '/* css-sanitizer failed to sanitize CSS */';
		}

		$css = CSSUtil::stringify( $css, [ 'minify' => true ] );
		// Sanity check copied from TemplateStyles: Ensure that $css doesn't break out the sanitizer
		if ( preg_match( '!</style!i', $css ) ) {
			return '/* Closing style tag found in resulting CSS */';
		}

		// Sanity check copied from TemplateStyles: Ensure that U+007F doesn't leak out through the sanitizer
		$css = strtr( $css, [ '\x7f' => '�' ] );

		return $css;
	}

	public function cssRender( Parser $parser, string $css ): string {
		$css = trim( $css );
		if ( $css === '' ) {
			return '';
		}
		$title = Title::newFromText( $css );
		$identifier = $this->config->get( 'CSSIdentifier' );
		$rawProtection = "$identifier=1";
		$headItem = '<!-- Begin Extension:CSS -->';

		if ( is_object( $title ) && $title->exists() ) {
			# Article actually in the db
			$params = "action=raw&ctype=text/css&$rawProtection";
			$url = $title->getLocalURL( $params );
			$headItem .= Html::linkedStyle( $url );
		} elseif ( $css[0] === '/' ) {
			# Regular file
			$base = $this->config->get( 'CSSPath' ) === false ?
				$this->config->get( 'StylePath' ) :
				$this->config->get( 'CSSPath' );
			// The replacement for \ to / is to workaround a path traversal,
			// per T369486.
			// TODO: Implement a proper URL parser. There may be more niche URL
			// shenanigans one could get up to that MediaWiki's parser does not
			// handle, but which the browser does. The most surefire way to
			// guarantee that no tomfoolery happens is to 100% replicate what
			// the browser does and not only like 90% of it.
			$path = str_replace( '\\', '/', $css );
			$url = wfAppendQuery( $base . $path, $rawProtection );

			# Verify the expanded URL is still using the base URL
			if ( strpos( wfExpandUrl( $url ), wfExpandUrl( $base ) ) === 0 ) {
				$headItem .= Html::linkedStyle( $url );
			} else {
				$headItem .= '<!-- Invalid/malicious path  -->';
			}
		} else {
			# sanitized user CSS
			$css = $this->sanitizeCSS( $css );

			# Encode data URI and append link tag
			$dataPrefix = 'data:text/css;charset=UTF-8;base64,';
			$url = $dataPrefix . base64_encode( $css );

			$headItem .= Html::linkedStyle( $url );
		}

		$headItem .= '<!-- End Extension:CSS -->';
		$parser->getOutput()->addHeadItem( $headItem );
		return '';
	}

	/**
	 * @param Parser $parser
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'css', [ $this, 'cssRender' ] );
	}

	/**
	 * @param RawAction $rawPage
	 * @param string &$text
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onRawPageViewBeforeOutput( $rawPage, &$text ) {
		$identifier = $this->config->get( 'CSSIdentifier' );

		if ( $rawPage->getRequest()->getBool( $identifier ) ) {
			$text = $this->sanitizeCSS( $text );
		}
	}
}
