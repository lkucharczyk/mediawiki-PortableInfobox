<?php
/**
 * @group PortableInfobox
 * @covers Wikia\PortableInfobox\Helpers\PortableInfoboxTemplateEngine
 */
class PortableInfoboxTemplateEngineTest extends MediaWikiTestCase {

	/**
	 * @covers Wikia\PortableInfobox\Helpers\PortableInfoboxTemplateEngine::isSupportedType
	 * @dataProvider isTypeSupportedInTemplatesDataProvider
	 */
	public function testIsTypeSupportedInTemplates( $type, $result, $description ) {
		$this->assertEquals(
			$result,
			Wikia\PortableInfobox\Helpers\PortableInfoboxTemplateEngine::isSupportedType( $type ),
			$description
		);
	}

	public function isTypeSupportedInTemplatesDataProvider() {
		return [
			[
				'type' => 'title',
				'result' => true,
				'description' => 'valid data type'
			],
			[
				'type' => 'invalidTestType',
				'result' => false,
				'description' => 'invalid data type'
			]
		];
	}

}