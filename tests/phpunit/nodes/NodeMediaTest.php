<?php

use PortableInfobox\Helpers\PortableInfoboxDataBag;
use PortableInfobox\Parser\Nodes\NodeMedia;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeMedia
 */
class NodeMediaTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		global $wgUseInstantCommons;
		$wgUseInstantCommons = false;
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getGalleryData
	 * @dataProvider galleryDataProvider
	 * @param $marker
	 * @param $expected
	 */
	public function testGalleryData( $marker, $expected ) {
		$this->assertEquals( $expected, NodeMedia::getGalleryData( $marker ) );
	}

	public function galleryDataProvider() {
		$markers = [
			"'\"`UNIQabcd-gAlLeRy-1-QINU`\"'",
			"'\"`UNIQabcd-gAlLeRy-2-QINU`\"'",
			"'\"`UNIQabcd-gAlLeRy-3-QINU`\"'"
		];
		PortableInfoboxDataBag::getInstance()->setGallery( $markers[0],
				new GalleryMock([
					[
						'image0_name.jpg',
						'image0_caption'
					],
					[
						'image01_name.jpg',
						'image01_caption'
					],
				]));
		PortableInfoboxDataBag::getInstance()->setGallery( $markers[1],
				new GalleryMock([
					[
						'image1_name.jpg',
						'image1_caption'
					]
				]));
		PortableInfoboxDataBag::getInstance()->setGallery( $markers[2], new GalleryMock() );

		return [
			[
				'marker' => $markers[0],
				'expected' => [
					[
						'label' => 'image0_caption',
						'title' => 'image0_name.jpg',
					],
					[
						'label' => 'image01_caption',
						'title' => 'image01_name.jpg',
					],
				]
			],
			[
				'marker' => $markers[1],
				'expected' => [
					[
						'label' => 'image1_caption',
						'title' => 'image1_name.jpg',
					]
				]
			],
			[
				'marker' => $markers[2],
				'expected' => []
			],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getTabberData
	 */
	public function testTabberData() {
		$input = '<div class="tabber"><div class="tabbertab" title="_title_"><p><a><img src="_src_"></a></p></div></div>';
		$expected = [
			[
				'label' => '_title_',
				'title' => '_src_',
			]
		];
		$this->assertEquals( $expected, NodeMedia::getTabberData( $input ) );
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getMarkers
	 * @dataProvider markersProvider
	 * @param $ext
	 * @param $value
	 * @param $expected
	 */
	public function testMarkers( $ext, $value, $expected ) {
		$this->assertEquals( $expected, PortableInfobox\Parser\Nodes\NodeMedia::getMarkers( $value, $ext ) );
	}

	public function markersProvider() {
		return [
			[
				'TABBER',
				"<div>\x7f'\"`UNIQ--tAbBeR-12345678-QINU`\"'\x7f</div>",
				[ "\x7f'\"`UNIQ--tAbBeR-12345678-QINU`\"'\x7f" ]
			],
			[
				'GALLERY',
				"\x7f'\"`UNIQ--tAbBeR-12345678-QINU`\"'\x7f<center>\x7f'\"`UNIQ--gAlLeRy-12345678-QINU`\"'\x7f</center>\x7f'\"`UNIQ--gAlLeRy-87654321-QINU`\"'\x7f",
				[ "\x7f'\"`UNIQ--gAlLeRy-12345678-QINU`\"'\x7f", "\x7f'\"`UNIQ--gAlLeRy-87654321-QINU`\"'\x7f" ]
			],
			[
				'GALLERY',
				"\x7f'\"`UNIQ--somethingelse-12345678-QINU`\"'\x7f",
				[ ]
			]
		];
	}


	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getData
	 * @dataProvider dataProvider
	 *
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testData( $markup, $params, $expected ) {
		$imageMock = empty( $params ) ? NULL : new ImageMock();
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$mock = $this->getMock(NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ]);
		$mock->expects( $this->any( ))
			->method( 'getFilefromTitle' )
			->willReturn( $imageMock );

		$this->assertEquals( $expected, $mock->getData() );
	}

	public function dataProvider() {
		// markup, params, expected
		return [
			[
				'<media source="img"></media>',
				[ ],
				[ [ ] ]
			],
			[
				'<media source="img"></media>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'alt' => 'Test.jpg', 'caption' => null ] ]
			],
			[
				'<media source="img"><alt><default>test alt</default></alt></media>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'alt' => 'test alt', 'caption' => null ] ]
			],
			[
				'<media source="img"><alt source="alt source"><default>test alt</default></alt></media>',
				[ 'img' => 'test.jpg', 'alt source' => 2 ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'alt' => 2, 'caption' => null ] ]
			],
			[
				'<media source="img"><alt><default>test alt</default></alt><caption source="img"/></media>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'alt' => 'test alt', 'caption' => 'test.jpg' ] ]
			],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::isEmpty
	 * @dataProvider isEmptyProvider
	 *
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testIsEmpty( $markup, $params, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, $params );

		$this->assertEquals( $expected, $node->isEmpty() );
	}

	public function isEmptyProvider() {
		return [
			[ '<media></media>', [ ], true ],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getSources
	 * @dataProvider sourcesProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testSources( $markup, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [ ] );

		$this->assertEquals( $expected, $node->getSources() );
	}

	public function sourcesProvider() {
		return [
			[
				'<media source="img"/>',
				[ 'img' ]
			],
			[
				'<media source="img"><default>{{{img}}}</default><alt source="img" /></media>',
				[ 'img' ]
			],
			[
				'<media source="img"><alt source="alt"/><caption source="cap"/></media>',
				[ 'img', 'alt', 'cap' ]
			],
			[
				'<media source="img"><alt source="alt"><default>{{{def}}}</default></alt><caption source="cap"/></media>',
				[ 'img', 'alt', 'def', 'cap' ] ],
			[
				'<media/>',
				[ ]
			],
			[
				'<image source="img"><caption source="cap"><format>Test {{{cap}}} and {{{fcap}}}</format></caption></image>',
				[ 'img', 'cap', 'fcap' ]
			]
		];
	}

	/** @dataProvider metadataProvider */
	public function testMetadata( $markup, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [ ] );

		$this->assertEquals( $expected, $node->getMetadata() );
	}

	public function metadataProvider() {
		return [
			[
				'<media source="img"><caption source="cap"><format>Test {{{cap}}} and {{{fcap}}}</format></caption></media>',
				[ 'type' => 'media', 'sources' => [
					'img' => [ 'label' => '', 'primary' => true ],
					'cap' => [ 'label' => '' ],
					'fcap' => [ 'label' => '' ]
				] ]
			]
		];
	}

	/**
	 * @dataProvider videoProvider
	 * @param $markup
	 * @param $params
	 * @param $expected
	 * @throws PortableInfobox\Parser\XmlMarkupParseErrorException
	 */
	public function testVideo( $markup, $params, $expected ) {
		$videoMock = new VideoMock();
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$mock = $this->getMock(NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ]);
		$mock->expects( $this->any( ))
			->method( 'getFilefromTitle' )
			->willReturn( $videoMock );

		$this->assertEquals( $expected, $mock->getData() );
	}

	public function videoProvider() {
		return [
			[
				'<media source="media" />',
				[ 'media' => 'test.webm' ],
				[
					[
						'url' => 'http://test.url',
						'name' => 'Test.webm',
						'alt' => 'Test.webm',
						'caption' => null
					]
				]
			],
			[
				'<media source="media" video="false" />',
				[ 'media' => 'test.webm' ],
				[ [ ] ]
			]
		];
	}

	/**
	 * @dataProvider audioProvider
	 * @param $markup
	 * @param $params
	 * @param $expected
	 * @throws PortableInfobox\Parser\XmlMarkupParseErrorException
	 */
	public function testAudio( $markup, $params, $expected ) {
		$audioMock = new AudioMock();
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$mock = $this->getMock(NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ]);
		$mock->expects( $this->any( ))
			->method( 'getFilefromTitle' )
			->willReturn( $audioMock );

		$this->assertEquals( $expected, $mock->getData() );
	}

	public function audioProvider() {
		return [
			[
				'<media source="media" />',
				[ 'media' => 'test.ogg' ],
				[
					[
						'url' => 'http://test.url',
						'name' => 'Test.ogg',
						'alt' => 'Test.ogg',
						'caption' => null
					]
				]
			],
			[
				'<media source="media" audio="false" />',
				[ 'media' => 'test.ogg' ],
				[ [ ] ]
			]
		];
	}
}

class ImageMock {
	public function getMediaType() {
		return MEDIATYPE_BITMAP;
	}

	public function getUrl() {
		return '';
	}
}

class VideoMock {
	public function getMediaType() {
		return MEDIATYPE_VIDEO;
	}

	public function getUrl() {
		return 'http://test.url';
	}
}

class AudioMock {
	public function getMediaType() {
		return MEDIATYPE_AUDIO;
	}

	public function getUrl() {
		return 'http://test.url';
	}
}

class GalleryMock {
	private $images;
	public function __construct( array $images = [] ) {
		$this->images = $images;
	}

	public function getImages() {
		return $this->images;
	}
}
