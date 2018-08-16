<?php

use PortableInfobox\Parser\Nodes\NodeUnimplemented;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeUnimplemented
 */
class NodeUnimplementedTest extends MediaWikiTestCase {

	/**
	 * @expectedException PortableInfobox\Parser\Nodes\UnimplementedNodeException
	 */
	public function testNewFromXML() {
		( new NodeUnimplemented(
			PortableInfobox\Parser\XmlParser::parseXmlString( "<foo/>" ),
			[]
		) )->getData();
	}

}
