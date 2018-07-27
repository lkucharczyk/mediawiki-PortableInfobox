<?php
class PortableInfoboxQueryService {
	const MCACHE_KEY = 'unconvertedinfoboxes-list';
	const CACHE_DURATION = 86400; // 24 hours

	public static function getNonPortableInfoboxes() {
		$data = WikiaDataAccess::cache( wfMemcKey( self::MCACHE_KEY ), CACHE_DURATION, function () {
			$dbr = wfGetDB( DB_SLAVE );
			return ( new WikiaSQL() )
				->SELECT( 'qc_value', 'qc_namespace', 'qc_title' )
				->FROM( 'querycache' )
				->WHERE( 'qc_type' )->EQUAL_TO( UnconvertedInfoboxesPage::UNCONVERTED_INFOBOXES_TYPE )
				->run( $dbr, function ( ResultWrapper $result ) {
					$out = [ ];
					while ( $row = $result->fetchRow() ) {
						$out[] = [
							'pageid' => $row[ 'qc_value' ],
							'title' => $row[ 'qc_title' ],
							'ns' => $row[ 'qc_namespace' ]
						];
					}
					return $out;
				} );
		} );

		return $data;
	}
}
