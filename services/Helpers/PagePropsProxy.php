<?php

namespace Wikia\PortableInfobox\Helpers;

class PagePropsProxy {

	public function get( $id, $property ) {
		$dbr = wfGetDB( DB_REPLICA );
		$propValue = $dbr->selectField(
			'page_props',
			'pp_value', 
			array(
				'pp_page' => $id,
				'pp_propname' => $property
			),
			__METHOD__
		);
		return $propValue;
	}

	public function set( $id, Array $props ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );
		foreach( $props as $sPropName => $sPropValue ) {
			$dbw->replace(
				"page_props",
				array(
					"pp_page",
					"pp_propname"
				),
				array(
					"pp_page" => $id,
					"pp_propname" => $sPropName,
					"pp_value" => $sPropValue
				),
				__METHOD__
			);
		}
		$dbw->endAtomic( __METHOD__ );
	}

}
