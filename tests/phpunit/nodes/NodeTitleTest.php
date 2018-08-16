<?php
/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeTitle
 */
class NodeTitleTest extends MediaWikiTestCase {

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeTitle::getData
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
		return [
			[ '<title source="test"/>', [ 'test' => 'test' ], [ 'value' => 'test' ] ],
			[ '<title source="test"><default>def</default></title>', [], [ 'value' => 'def' ] ],
			[ '<title source="test"><default>def</default></title>', [],
			  [ 'value' => 'def' ] ],
			[ '<title source="test"><default>def</default></title>', [ 'l' => 1 ],
			  [ 'value' => 'def' ] ],
			[ '<title source="test"><default>def</default></title>', [ 'l' => 1 ],
			  [ 'value' => 'def' ] ],
			[ '<title source="test"><default>def</default></title>', [ 'test' => 1 ],
			  [ 'value' => 1 ] ],
			[ '<title></title>', [], [ 'value' => null ] ],
			[ '<title source="test"><format>{{{test}}}%</format><default>def</default></title>', [ 'test' => 1 ],
			  [ 'value' => '{{{test}}}%' ] ],
			[ '<title source="test"><format>{{{not_defined_var}}}%</format><default>def</default></title>', [ 'test' => 1 ],
				[ 'value' => '{{{not_defined_var}}}%' ] ],
			[ '<title source="test"><format>{{{test}}}%</format><default>def</default></title>', [],
				[ 'value' => 'def' ] ],
			[ '<title source="test"><format>{{{test}}}%</format></title>', [ 'test' => 0 ],
				[ 'value' => '{{{test}}}%' ] ],
		];
	}

}
