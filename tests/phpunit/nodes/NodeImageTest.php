<?php

use PortableInfobox\Helpers\PortableInfoboxDataBag;
use PortableInfobox\Parser\Nodes\NodeImage;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeImage
 */
class NodeImageTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		global $wgUseInstantCommons;
		$wgUseInstantCommons = false;
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::getGalleryData
	 * @dataProvider galleryDataProvider
	 * @param $marker
	 * @param $expected
	 */
	public function testGalleryData( $marker, $expected ) {
		$this->assertEquals( $expected, NodeImage::getGalleryData( $marker ) );
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
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::getTabberData
	 */
	public function testTabberData() {
		$input = '<div class="tabber"><div class="tabbertab" title="_title_"><p><a><img src="_src_"></a></p></div></div>';
		$expected = [
			[
				'label' => '_title_',
				'title' => '_src_',
			]
		];
		$this->assertEquals( $expected, NodeImage::getTabberData( $input ) );
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::getMarkers
	 * @dataProvider markersProvider
	 * @param $ext
	 * @param $value
	 * @param $expected
	 */
	public function testMarkers( $ext, $value, $expected ) {
		$this->assertEquals( $expected, PortableInfobox\Parser\Nodes\NodeImage::getMarkers( $value, $ext ) );
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
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::getData
	 * @dataProvider dataProvider
	 *
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testData( $markup, $params, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, $params );

		$this->assertEquals( $expected, $node->getData() );
	}

	public function dataProvider() {
		// markup, params, expected
		return [
			[
				'<image source="img"></image>',
				[ ],
				[ [ 'url' => '', 'name' => '', 'key' => '', 'alt' => null, 'caption' => null, 'isVideo' => false ] ]
			],
			[
				'<image source="img"></image>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'key' => 'Test.jpg', 'alt' => 'Test.jpg', 'caption' => null, 'isVideo' => false ] ]
			],
			[
				'<image source="img"><alt><default>test alt</default></alt></image>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'key' => 'Test.jpg', 'alt' => 'test alt', 'caption' => null, 'isVideo' => false ] ]
			],
			[
				'<image source="img"><alt source="alt source"><default>test alt</default></alt></image>',
				[ 'img' => 'test.jpg', 'alt source' => 2 ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'key' => 'Test.jpg', 'alt' => 2, 'caption' => null, 'isVideo' => false ] ]
			],
			[
				'<image source="img"><alt><default>test alt</default></alt><caption source="img"/></image>',
				[ 'img' => 'test.jpg' ],
				[ [ 'url' => '', 'name' => 'Test.jpg', 'key' => 'Test.jpg', 'alt' => 'test alt', 'caption' => 'test.jpg', 'isVideo' => false ] ]
			],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::isEmpty
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
			[ '<image></image>', [ ], true ],
		];
	}

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeImage::getSources
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
				'<image source="img"/>',
				[ 'img' ]
			],
			[
				'<image source="img"><default>{{{img}}}</default><alt source="img" /></image>',
				[ 'img' ]
			],
			[
				'<image source="img"><alt source="alt"/><caption source="cap"/></image>',
				[ 'img', 'alt', 'cap' ]
			],
			[
				'<image source="img"><alt source="alt"><default>{{{def}}}</default></alt><caption source="cap"/></image>',
				[ 'img', 'alt', 'def', 'cap' ] ],
			[
				'<image/>',
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
				'<image source="img"><caption source="cap"><format>Test {{{cap}}} and {{{fcap}}}</format></caption></image>',
				[ 'type' => 'image', 'sources' => [
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
		$fileMock = new FileMock();
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

		$mock = $this->getMock(NodeImage::class, [ 'getFilefromTitle' ], [ $xmlObj, $params ]);
		$mock->expects( $this->any( ))
			->method( 'getFilefromTitle' )
			->willReturn( $fileMock );

		$this->assertEquals( $expected, $mock->getData() );
	}

	public function videoProvider() {
		return [
			[
				'<image source="img" />',
				[ 'img' => 'test.jpg' ],
				[
					[
						'url' => 'http://test.url',
						'name' => 'Test.jpg',
						'key' => 'Test.jpg',
						'alt' => 'Test.jpg',
						'caption' => null,
						'isVideo' => true
					]
				]
			]
		];
	}
}

class FileMock {
	public function getMediaType() {
		return "VIDEO";
	}

	public function getUrl() {
		return 'http://test.url';
	}
}

class GalleryMock {
	private $images;
	public function __construct( Array $images = [] ) {
		$this->images = $images;
	}

	public function getImages() {
		return $this->images;
	}
}
