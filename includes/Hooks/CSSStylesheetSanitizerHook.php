<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * This file is based on TemplateStyles's TemplateStylesStylesheetSanitizerHook.php:
 * https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+/refs/heads/master/includes/Hooks/TemplateStylesStylesheetSanitizerHook.php
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\CSS\Hooks;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Sanitizer\StylePropertySanitizer;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CSSStylesheetSanitizer" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CSSStylesheetSanitizerHook {
	/**
	 * Allows for adjusting or replacing the StylesheetSanitizer.
	 * For example, you might add, remove, or redefine at-rule sanitizers
	 *
	 * @param StylesheetSanitizer &$sanitizer StylesheetSanitizer to be used for sanitization.
	 *  The array returned by `$sanitizer->getRuleSanitizers()` will use the at-rule names (including the '@') as keys.
	 *  The style rule sanitizer has key 'styles'
	 * @param StylePropertySanitizer $propertySanitizer StylePropertySanitizer being used for sanitization,
	 *  for use in adding or redefining rule sanitizers
	 * @param MatcherFactory $matcherFactory MatcherFactory being used, for use in adding or redefining rule sanitizers
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onCSSStylesheetSanitizer(
		StylesheetSanitizer &$sanitizer,
		StylePropertySanitizer $propertySanitizer,
		MatcherFactory $matcherFactory
	);
}
