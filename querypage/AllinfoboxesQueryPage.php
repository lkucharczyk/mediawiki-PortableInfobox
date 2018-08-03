<?php

class AllinfoboxesQueryPage extends PageQueryPage {

	const ALL_INFOBOXES_TYPE = 'AllInfoboxes';
	private static $subpagesBlacklist = [ 'doc', 'draft', 'test' ];

	function __construct() {
		parent::__construct( self::ALL_INFOBOXES_TYPE );
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

	function getQueryInfo() {
		return [
			'tables' => [ 'page' ],
			'fields' => [
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'id' => 'page_id',
				'value' => 'page_title'
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
	 * @param bool $limit Only for consistency
	 * @param bool $ignoreErrors Only for consistency
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
	 * @param bool $limit Only for consistency
	 * @param bool $offset Only for consistency
	 *
	 * @return ResultWrapper
	 */
	public function reallyDoQuery( $limit = false, $offset = false ) {
		$res = parent::reallyDoQuery( false );
		$out = [];

		while ( $row = $res->fetchObject() ) {
			if($this->filterInfoboxes( $row )) {
				$out[] = $row;
			} 
		}

		return new FakeResultWrapper($out);
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
		$title = Title::newFromID( $tmpl->id );

		return $title &&
			$title->exists() &&
			$this->hasInfobox( $title );
	}
}
