<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See http://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * @file
 * @ingroup Extensions
 * @version 2.0
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @author Rusty Burchfield
 * @copyright © 2007-2010 Aran Dunkley
 * @copyright © 2011 Rusty Burchfield
 * @licence GNU General Public Licence 2.0 or later
 */

if ( !defined( 'MEDIAWIKI') ) die('Not an entry point.' );

define( 'CSS_VERSION', '2.0, 2011-10-27' );

$wgCSSMagic = 'css';
$wgCSSPath = false;

$wgHooks['ParserFirstCallInit'][] = 'wfCSSParserFirstCallInit';
$wgHooks['LanguageGetMagic'][] = 'wfCSSLanguageGetMagic';

$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'CSS',
	'author'         => array ( 'Aran Dunkley', 'Rusty Burchfield' ),
	'descriptionmsg' => 'css-desc',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:CSS',
	'version'        => CSS_VERSION,
);

$wgExtensionMessagesFiles['CSS'] = dirname( __FILE__ ) . '/' . 'CSS.i18n.php';

function wfCSSRender( &$parser, $css ) {
	global $wgCSSPath, $wgScriptPath;

	$css = trim( $css );
	$title = Title::newFromText( $css );
	if ( is_object( $title ) && $title->isKnown() ) {
		# "blue link" Article
		$url = $title->getLocalURL( 'action=raw&ctype=text/css' );
		$headItem = HTML::linkedStyle( $url );
	} elseif ( $css[0] == '/' ) {
		# Regular file
		$base = $wgCSSPath === false ? $wgScriptPath : $wgCSSPath;
		$headItem = HTML::linkedStyle( $base . $css );
	} else {
		# Inline CSS
		$headItem = HTML::inlineStyle( Sanitizer::checkCss( $css ) );
	}

	$headItem = FormatJson::encode( $headItem );
	$script = HTML::inlineScript( "$('head').append( $headItem );" );
	return array( $script, 'isHTML' => true );
}

function wfCSSParserFirstCallInit( $parser ) {
	global $wgCSSMagic;
	$parser->setFunctionHook( $wgCSSMagic, 'wfCSSRender' );
	return true;
}

function wfCSSLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $wgCSSMagic;
	$magicWords[$wgCSSMagic] = array( $langCode, $wgCSSMagic );
	return true;
}
