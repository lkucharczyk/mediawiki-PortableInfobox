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
	 * @covers       PortableInfobox\Helpers\PortableInfoboxDataBag
	 * @dataProvider galleryDataProvider
	 * @param $marker
	 * @param $expected
	 */
	public function testGalleryData( $marker, $gallery, $expected ) {
		PortableInfoboxDataBag::getInstance()->setGallery( $marker, $gallery );
		$this->assertEquals( $expected, NodeMedia::getGalleryData( $marker ) );
	}

	public function galleryDataProvider() {
		$markers = [
			"'\"`UNIQabcd-gAlLeRy-1-QINU`\"'",
			"'\"`UNIQabcd-gAlLeRy-2-QINU`\"'",
			"'\"`UNIQabcd-gAlLeRy-3-QINU`\"'"
		];
		$galleries = [
			new GalleryMock( [
				[
					'image0_name.jpg',
					'image0_caption'
				],
				[
					'image01_name.jpg',
					'image01_caption'
				],
			] ),
			new GalleryMock( [
				[
					'image1_name.jpg',
					'image1_caption'
				]
			] ),
			new GalleryMock()
		];

		return [
			[
				'marker' => $markers[0],
				'gallery' => $galleries[0],
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
				'gallery' => $galleries[1],
				'expected' => [
					[
						'label' => 'image1_caption',
						'title' => 'image1_name.jpg',
					]
				]
			],
			[
				'marker' => $markers[2],
				'gallery' => $galleries[2],
				'expected' => []
			],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeMedia::getTabberData
	 * @covers       PortableInfobox\Helpers\HtmlHelper
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
				[]
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
		$imageMock = empty( $params ) ? null : new ImageMock();
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$mock = $this->getMock( NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ] );
		$mock->expects( $this->any() )
			->method( 'getFilefromTitle' )
			->willReturn( $imageMock );

		$this->assertEquals( $expected, $mock->getData() );
	}

	public function dataProvider() {
		// markup, params, expected
		return [
			[
				'<media source="img"></media>',
				[],
				[ [] ]
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
			[ '<media></media>', [], true ],
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
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [] );

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
				[]
			],
			[
				'<image source="img"><caption source="cap"><format>Test {{{cap}}} and {{{fcap}}}</format></caption></image>',
				[ 'img', 'cap', 'fcap' ]
			]
		];
	}

	/** @dataProvider metadataProvider */
	public function testMetadata( $markup, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [] );

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

		$mock = $this->getMock( NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ] );
		$mock->expects( $this->any() )
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
				[ [] ]
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

		$mock = $this->getMock( NodeMedia::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ] );
		$mock->expects( $this->any() )
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
				[ [] ]
			]
		];
	}

	/**
	 * @covers PortableInfobox\Parser\Nodes\NodeMedia::isTypeAllowed
	 * @covers PortableInfobox\Parser\Nodes\NodeAudio
	 * @covers PortableInfobox\Parser\Nodes\NodeImage
	 * @covers PortableInfobox\Parser\Nodes\NodeVideo
	 * @dataProvider isTypeAllowedProvider
	 * @param $markup
	 * @param $expected
	 * @throws PortableInfobox\Parser\XmlMarkupParseErrorException
	 */
	public function testIsTypeAllowed( $markup, $expected ) {
		$types = [ MEDIATYPE_BITMAP, MEDIATYPE_DRAWING, MEDIATYPE_VIDEO, MEDIATYPE_AUDIO, 'unknown' ];

		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [] );

		$reflection = new ReflectionClass( $node );
		$reflection_method = $reflection->getMethod( 'isTypeAllowed' );
		$reflection_method->setAccessible( true );

		foreach ( $types as $i => $type ) {
			$this->assertEquals( $expected[$i], $reflection_method->invoke( $node, $type ) );
		}
	}

	public function isTypeAllowedProvider() {
		return [
			[
				'<media />',
				[ true, true, true, true, false ]
			],
			[
				'<media image="false" />',
				[ false, false, true, true, false ]
			],
			[
				'<media video="false" />',
				[ true, true, false, true, false ]
			],
			[
				'<media audio="false" />',
				[ true, true, true, false, false ]
			],
			[
				'<media image="false" video="false" audio="false" />',
				[ false, false, false, false, false ]
			],
			[
				'<image />',
				[ true, true, true, false, false ]
			],
			[
				'<image video="false" />',
				[ true, true, false, false, false ]
			],
			[
				'<video />',
				[ false, false, true, false, false ]
			],
			[
				'<audio />',
				[ false, false, false, true, false ]
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
