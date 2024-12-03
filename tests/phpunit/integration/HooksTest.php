<?php

namespace MediaWiki\Extension\CSS\Tests\Integration;

use MediaWiki\Extension\CSS\Hooks;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOutput;

/**
 * @covers \MediaWiki\Extension\CSS\Hooks
 * @group Database
 */
class HooksTest extends MediaWikiIntegrationTestCase {
	private function newInstance(): Hooks {
		$services = $this->getServiceContainer();
		return new Hooks(
			$services->getMainConfig(),
			$services->getHookContainer(),
			$services->getTitleFactory(),
			$services->getUrlUtils()
		);
	}

	/**
	 * @dataProvider provideCssRender
	 */
	public function testCssRender( string $expected, string $css ) {
		$hooks = $this->newInstance();

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->method( 'addHeadItem' )->with( $expected );

		$parser = $this->createMock( Parser::class );
		$parser->method( 'getOutput' )
			->willReturn( $parserOutput );

		$result = $hooks->cssRender( $parser, $css );

		// The result is always empty.
		$this->assertSame( '', $result );
	}

	public static function provideCssRender() {
		return [
			[ '', '' ],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				'href="/skins/skins/MyStyles.css?css-extension=1">' .
				'<!-- End Extension:CSS -->',
				'/skins/MyStyles.css',
			],
			[
				'<!-- Begin Extension:CSS --><!-- Invalid/malicious path  --><!-- End Extension:CSS -->',
				'/../../BadStyles.css',
			],
			// Regression test for T369486:
			[
				'<!-- Begin Extension:CSS --><!-- Invalid/malicious path  --><!-- End Extension:CSS -->',
				'/..\index.php?title=CSS/Path traversal/styles.css&action=raw&ctype=text/css',
			],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				// '/* css-sanitizer failed to parse CSS */'
				'href="data:text/css;charset=UTF-8;base64,LyogY3NzLXNhbml0aXplciBmYWlsZWQgdG8gcGFyc2UgQ1NTICov">' .
				'<!-- End Extension:CSS -->',
				'{',
			],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				// '/* css-sanitizer failed to sanitize CSS */'
				'href="data:text/css;charset=UTF-8;base64,LyogY3NzLXNhbml0aXplciBmYWlsZWQgdG8gc2FuaXRpemUgQ1NTICov">' .
				'<!-- End Extension:CSS -->',
				<<<EOT
				  body {{
				    background: yellow;
				    font-size: 20pt;
				    color: red;
				  }}
				EOT,
			],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				'href="data:text/css;charset=UTF-8;base64,' .
				// 'body{background:yellow;font-size:20pt;color:red}'
				'Ym9keXtiYWNrZ3JvdW5kOnllbGxvdztmb250LXNpemU6MjBwdDtjb2xvcjpyZWR9">' .
				'<!-- End Extension:CSS -->',
				<<<EOT
				  body {
				    background: yellow;
				    font-size: 20pt;
				    color: red;
				  }
				EOT,
			],
		];
	}
}
