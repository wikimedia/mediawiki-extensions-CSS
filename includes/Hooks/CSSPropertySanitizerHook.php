<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * This file is based on TemplateStyles's TemplateStylesPropertySanitizerHook.php:
 * https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+/refs/heads/master/includes/Hooks/TemplateStylesPropertySanitizerHook.php
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\CSS\Hooks;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Sanitizer\StylePropertySanitizer;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CSSPropertySanitizer" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CSSPropertySanitizerHook {
	/**
	 * Allows for adjusting or replacing the StylePropertySanitizer used when sanitizing style rules.
	 * For example, you might add, remove, or redefine known properties
	 *
	 * @param StylePropertySanitizer &$propertySanitizer StylePropertySanitizer to be used for sanitization
	 * @param MatcherFactory $matcherFactory MatcherFactory being used, for use in adding or redefining known
	 *  properties or replacing the entire sanitizer
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onCSSPropertySanitizer(
		StylePropertySanitizer &$propertySanitizer,
		MatcherFactory $matcherFactory
	);
}
