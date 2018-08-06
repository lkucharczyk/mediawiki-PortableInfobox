<?php

class AllinfoboxesQueryPage extends PageQueryPage {

	const ALL_INFOBOXES_TYPE = 'AllInfoboxes';
	private static $subpagesBlacklist = [];

	function __construct() {
		parent::__construct( self::ALL_INFOBOXES_TYPE );

		$blacklist = $this->getConfig( 'AllInfoboxesSubpagesBlacklist' );
		if( is_array( $blacklist ) ) {
			self::$subpagesBlacklist = $blacklist;
		}
	}

	function getGroupName() {
		return 'pages';
	}

	public function sortDescending() {
		return false;
	}

	public function isExpensive() {
		return true;
	}

	public function isCached() {
		return $this->isExpensive() && (
			$this->getConfig()->get( 'MiserMode' ) ||
			$this->getConfig()->get( 'AllInfoboxesMiserMode' )
		);
	}
	
	public function getOrderFields() {
		return [ 'title' ];
	}

	public function getCacheOrderFields() {
		return $this->getOrderFields();
	}
	
	function getQueryInfo() {
		return [
			'tables' => [ 'page' ],
			'fields' => [
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'value' => 'page_id'
			],
			'conds' => [
				'page_is_redirect' => 0,
				'page_namespace' => NS_TEMPLATE
			]
		];
	}

	/**
	 * Update the querycache table
	 *
	 * @see QueryPage::recache
	 *
	 * @param bool $limit Only for consistency
	 * @param bool $ignoreErrors Whether to ignore database errors
	 *
	 * @return int number of rows updated
	 */
	public function recache( $limit = false, $ignoreErrors = true ) {
		$res = parent::recache( false, $ignoreErrors );

		Hooks::run( 'AllInfoboxesQueryRecached' );
		return $res;
	}

	/**
	 * Queries all templates and get only those with portable infoboxes
	 *
	 * @see QueryPage::reallyDoQuery 
	 *
	 * @param int|bool $limit Numerical limit or false for no limit
	 * @param int|bool $offset Numerical offset or false for no limit
	 *
	 * @return ResultWrapper
	 */
	public function reallyDoQuery( $limit = false, $offset = false ) {
		$res = parent::reallyDoQuery( false );
		$out = [];

		$maxResults = $this->getMaxResults();
		if ( $limit == 0 ) {
			$limit = $maxResults;
		} else {
			$limit = min( $limit, $maxResults );
		}

		while ( $limit >= 0 && $row = $res->fetchObject() ) {
			if( $this->filterInfoboxes( $row ) && $offset-- <= 0 ) {
				$out[] = $row;
				$limit--;
			} 
		}

		return new FakeResultWrapper( $out );
	}

	public function addTitleToCache( Title $title ) {
		if ( !$this->hasInfobox( $title ) ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->doAtomicSection(
			__METHOD__,
			function ( IDatabase $dbw, $fname ) use ( $title ) {
				$dbw->insert( 'querycache', [
					'qc_type' => $this->getName(),
					'qc_namespace' => $title->getNamespace(),
					'qc_title' => $title->getDBkey()
				], $fname );
			}
		);

		Hooks::run( 'AllInfoboxesQueryRecached' );
	}

	private function hasInfobox( Title $title ) {
		// omit subages from blacklist
		return !(
				$title->isSubpage() &&
				in_array( mb_strtolower( $title->getSubpageText() ), self::$subpagesBlacklist )
			) &&
			!empty( PortableInfoboxDataService::newFromTitle( $title )->getData() );
	}

	private function filterInfoboxes( $tmpl ) {
		$title = Title::newFromID( $tmpl->value );

		return $title &&
			$title->exists() &&
			$this->hasInfobox( $title );
	}
}
