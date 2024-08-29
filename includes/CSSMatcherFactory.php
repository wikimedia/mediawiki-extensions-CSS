<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * This file is based on TemplateStylesMatcherFactory.php:
 * https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+/refs/heads/master/includes/TemplateStylesMatcherFactory.php
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\CSS;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Grammar\NothingMatcher;

class CSSMatcherFactory extends MatcherFactory {

	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function urlstring( $type ) {
		if ( isset( $this->cache[__METHOD__] ) ) {
			return $this->cache[__METHOD__];
		}

		$matcher = $this->cache[__METHOD__] = new NothingMatcher;
		return $matcher;
	}

	/**
	 * @inheritDoc
	 */
	public function url( $type ) {
		if ( isset( $this->cache[__METHOD__] ) ) {
			return $this->cache[__METHOD__];
		}

		$matcher = $this->cache[__METHOD__] = new NothingMatcher;
		return $matcher;
	}

}
