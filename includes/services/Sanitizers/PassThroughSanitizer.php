<?php

namespace PortableInfobox\Sanitizers;

class PassThroughSanitizer extends NodeSanitizer {
	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize( $data ) {
		return $data;
	}
}
