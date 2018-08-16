<?php

namespace PortableInfobox\Sanitizers;

class NodeTitleSanitizer extends NodeSanitizer {
	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize( $data ) {
		$data['value'] = $this->sanitizeElementData( $data['value'] );

		return $data;
	}
}
