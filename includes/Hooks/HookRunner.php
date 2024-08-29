<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * This file is based on TemplateStyles's HookRunner.php:
 * https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+/refs/heads/master/includes/Hooks/HookRunner.php
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\CSS\Hooks;

use MediaWiki\HookContainer\HookContainer;
use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Sanitizer\StylePropertySanitizer;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	CSSPropertySanitizerHook,
	CSSStylesheetSanitizerHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onCSSPropertySanitizer(
		StylePropertySanitizer &$propertySanitizer,
		MatcherFactory $matcherFactory
	) {
		return $this->hookContainer->run(
			'CSSPropertySanitizer',
			[ &$propertySanitizer, $matcherFactory ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onCSSStylesheetSanitizer(
		StylesheetSanitizer &$sanitizer,
		StylePropertySanitizer $propertySanitizer,
		MatcherFactory $matcherFactory
	) {
		return $this->hookContainer->run(
			'CSSStylesheetSanitizer',
			[ &$sanitizer, $propertySanitizer, $matcherFactory ]
		);
	}
}
