<?php

use PortableInfobox\Sanitizers\SanitizerBuilder;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Sanitizers\SanitizerBuilder
 */
class SanitizerBuilderTest extends MediaWikiTestCase {

	/**
	 * @param $type
	 * @param $expected
	 * @dataProvider createFromTypeProvide
	 */
	public function testCreateFromType( $type, $expected ) {
		$this->assertType( $expected, SanitizerBuilder::createFromType( $type ) );
	}

	public function createFromTypeProvide() {
		return [
			[
				'data',
				 PortableInfobox\Sanitizers\NodeDataSanitizer::class
			],
			[
				'horizontal-group-content',
				PortableInfobox\Sanitizers\NodeHorizontalGroupSanitizer::class
			],
			[
				'title',
				PortableInfobox\Sanitizers\NodeTitleSanitizer::class
			],
			[
				'image',
				PortableInfobox\Sanitizers\NodeImageSanitizer::class
			],
			[
				'unknown',
				PortableInfobox\Sanitizers\PassThroughSanitizer::class
			],
		];
	}
}