<?php
/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeHeader
 */
class NodeHeaderTest extends MediaWikiTestCase {

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeHeader::getData
	 * @covers       PortableInfobox\Parser\Nodes\Node::getInnerValue
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
			[ '<header></header>', [], [ 'value' => '' ] ],
			[ '<header>kjdflkja dafkjlsdkfj</header>', [], [ 'value' => 'kjdflkja dafkjlsdkfj' ] ],
			[ '<header>kjdflkja<ref>dafkjlsdkfj</ref></header>', [], [ 'value' => 'kjdflkja<ref>dafkjlsdkfj</ref>' ] ],
		];
	}

}
