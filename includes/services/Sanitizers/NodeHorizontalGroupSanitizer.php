<?php

namespace PortableInfobox\Sanitizers;

class NodeHorizontalGroupSanitizer extends NodeSanitizer {
	protected $allowedTags = [ 'a' ];

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize( $data ) {
		foreach ( $data['labels'] as $key => $label ) {
			$sanitizedLabel = $this->sanitizeElementData( $label );
			if ( !empty( $sanitizedLabel ) ) {
				$data['labels'][$key] = $sanitizedLabel;
			}
		}

		return $data;
	}
}
