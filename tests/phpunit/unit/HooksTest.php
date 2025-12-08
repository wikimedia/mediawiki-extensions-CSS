<?php

namespace MediaWiki\Extension\CSS\Tests\Unit;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\CSS\Hooks;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CSS\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {
	/**
	 * @dataProvider provideCssRender
	 */
	public function testCssRender( string $expected, string $css, bool $exists ) {
		$title = $this->createMock( Title::class );
		$title->method( 'exists' )->willReturn( $exists );
		$title->method( 'getLocalURL' )
			->with( [
				'action' => 'raw',
				'ctype' => 'text/css',
				'css-extension' => '1',
			] )
			->willReturn( '/w/index.php?title=MyStyles.css&action=raw&ctype=text%2Fcss&css-extension=1' );

		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->method( 'newFromText' )
			->with( $css )
			->willReturn( $title );

		$urlUtils = $this->createMock( UrlUtils::class );
		$urlUtils->method( 'expand' )
			->willReturnMap( [
				[ '', PROTO_FALLBACK, '' ],
				[ '/w/skins', PROTO_FALLBACK, 'https://www.example.com/w/skins' ],
				[
					'/w/skins/skins/MyStyles.css?css-extension=1',
					PROTO_FALLBACK,
					'https://www.example.com/w/skins/skins/MyStyles.css?css-extension=1'
				],
				[
					'/w/skins/../../BadStyles.css?css-extension=1',
					PROTO_FALLBACK,
					'https://www.example.com/BadStyles.css?css-extension=1'
				],
			] );

		$hooks = new Hooks(
			new HashConfig( [
				'CSSIdentifier' => 'css-extension',
				'CSSPath' => null,
				'StylePath' => '/w/skins',
			] ),
			$this->createNoOpMock( HookContainer::class ),
			$titleFactory,
			$urlUtils
		);

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
			[ '', '', false ],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				'href="/w/index.php?title=MyStyles.css&amp;action=raw&amp;ctype=text%2Fcss&amp;css-extension=1">' .
				'<!-- End Extension:CSS -->',
				'MyStyles.css',
				true
			],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				'href="/w/skins/skins/MyStyles.css?css-extension=1">' .
				'<!-- End Extension:CSS -->',
				'/skins/MyStyles.css',
				false
			],
			[
				'<!-- Begin Extension:CSS --><!-- Invalid/malicious path  --><!-- End Extension:CSS -->',
				'/../../BadStyles.css',
				false,
			],
		];
	}
}
