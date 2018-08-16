<?php

namespace PortableInfobox\Sanitizers;

interface NodeTypeSanitizerInterface {
	/**
	 * @desc sanitize infobox data element
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize( $data );
}
