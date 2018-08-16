<?php

namespace PortableInfobox\Sanitizers;

class SanitizerBuilder {

	/**
	 * @desc provide sanitizer for a given node type
	 *
	 * @param string $type
	 * @return NodeSanitizer
	 */
	public static function createFromType( $type ) {
		switch ( $type ) {
			case 'data':
				return new NodeDataSanitizer();
			case 'horizontal-group-content':
				return new NodeHorizontalGroupSanitizer();
			case 'title':
				return new NodeTitleSanitizer();
			case 'image':
				return new NodeImageSanitizer();
			default:
				return new PassThroughSanitizer();
		}
	}
}
