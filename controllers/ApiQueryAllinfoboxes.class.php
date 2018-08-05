<?php

class ApiQueryAllinfoboxes extends ApiQueryBase {

	const CACHE_TTL = 86400;
	const MCACHE_KEY = 'allinfoboxes-list';

	public function execute() {
		
		$db = $this->getDB();
		$res = $this->getResult();
		$cache = ObjectCache::getMainWANInstance();
		$cachekey = $cache->makeKey( __CLASS__, self::MCACHE_KEY );

		$data = $cache->getWithSetCallback( $cachekey, self::CACHE_TTL, function () use ( $db ) {
			global $wgPortableInfoboxApiCanTriggerRecache;

			$out = [];

			if( $wgPortableInfoboxApiCanTriggerRecache ) {
				$res = $db->select(
					'querycache_info',
					[ 'timestamp' => 'qci_timestamp' ], 
					[ 'qci_type' => AllinfoboxesQueryPage::ALL_INFOBOXES_TYPE ],
					__METHOD__
				);

				$recache = intval( wfTimestamp( TS_UNIX, wfTimestampNow() ) ) - self::CACHE_TTL;
				$lastcache = wfTimestamp( TS_UNIX, $res->fetchObject()->timestamp );

				if( $lastcache < $recache ) {
					(new AllinfoboxesQueryPage())->recache();
				}
			}

			$res = $db->select(
				'querycache',
				[ 'qc_value', 'qc_title', 'qc_namespace' ], 
				[ 'qc_type' => AllinfoboxesQueryPage::ALL_INFOBOXES_TYPE ],
				__METHOD__
			);

			while( $row = $res->fetchObject() ) {
				$out[] = [
					'pageid' => $row->qc_value,
					'title' => $row->qc_title,
					'label' => $this->createLabel( $row->qc_title ),
					'ns' => $row->qc_namespace
				];
			}

			return $out;
		} );

		foreach ( $data as $id => $infobox ) {
			$res->addValue( [ 'query', 'allinfoboxes' ], null, $infobox );
		}
		$res->addIndexedTagName( [ 'query', 'allinfoboxes' ], 'i' );
	}

	public function getVersion() {
		return __CLASS__ . '$Id$';
	}

	/**
	 * @desc As a infobox template label we want to return a nice, clean text, without e.g. '_' signs
	 * @param $text infobox template title
	 * @return String
	 */
	private function createLabel( $text ) {
		$title = Title::newFromText( $text , NS_TEMPLATE );

		if ( $title ) {
			return $title->getText();
		}

		return $text;
	}
}
