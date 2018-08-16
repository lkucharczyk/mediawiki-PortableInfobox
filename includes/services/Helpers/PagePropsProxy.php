<?php

namespace PortableInfobox\Helpers;

class PagePropsProxy {

	public function get( $id, $property ) {
		$dbr = wfGetDB( DB_REPLICA );
		$propValue = $dbr->selectField(
			'page_props',
			'pp_value',
			[
				'pp_page' => $id,
				'pp_propname' => $property
			],
			__METHOD__
		);
		return $propValue;
	}

	public function set( $id, array $props ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );
		foreach ( $props as $sPropName => $sPropValue ) {
			$dbw->replace(
				"page_props",
				[
					"pp_page",
					"pp_propname"
				],
				[
					"pp_page" => $id,
					"pp_propname" => $sPropName,
					"pp_value" => $sPropValue
				],
				__METHOD__
			);
		}
		$dbw->endAtomic( __METHOD__ );
	}

}
