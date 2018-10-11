<?php

use PortableInfobox\Helpers\PortableInfoboxParsingHelper;

class AllinfoboxesQueryPage extends PageQueryPage {

	const ALL_INFOBOXES_TYPE = 'AllInfoboxes';
	private $compatibleMode;
	private $subpagesBlacklist = [];
	private $parsingHelper;

	function __construct() {
		parent::__construct( self::ALL_INFOBOXES_TYPE );

		$blacklist = $this->getConfig( 'AllInfoboxesSubpagesBlacklist' );
		if ( is_array( $blacklist ) ) {
			$this->subpagesBlacklist = $blacklist;
		}

		$this->compatibleMode = $this->getConfig()->get( 'AllInfoboxesCompatibleMode' );
		$this->parsingHelper = new PortableInfoboxParsingHelper();
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
		$query = [
			'tables' => [ 'page' ],
			'fields' => [
				'namespace' => 'page.page_namespace',
				'title' => 'page.page_title',
				'value' => 'page.page_id'
			],
			'conds' => [
				'page.page_is_redirect' => 0,
				'page.page_namespace' => NS_TEMPLATE
			]
		];

		if ( !$this->compatibleMode ) {
			$query = array_merge_recursive( $query, [
				'tables' => [ 'revision', 'text' ],
				'fields' => [
					'text' => 'text.old_text'
				],
				'join_conds' => [
					'revision' => [ 'LEFT JOIN', 'page.page_latest = revision.rev_id' ],
					'text' => [ 'LEFT JOIN', 'revision.rev_text_id = text.old_id AND text.old_flags = "utf-8"' ]
				]
			] );
		}

		return $query;
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
			if ( $this->filterInfoboxes( $row ) && $offset-- <= 0 ) {
				$out[] = $row;
				$limit--;
			}
		}

		return new FakeResultWrapper( $out );
	}

	private function filterInfoboxes( $tmpl ) {
		$title = Title::newFromID( $tmpl->value );

		return $title &&
			$title->exists() &&
			!(
				$title->isSubpage() &&
				in_array( mb_strtolower( $title->getSubpageText() ), $this->subpagesBlacklist )
			) &&
			(
				$this->compatibleMode ?
				!empty( PortableInfoboxDataService::newFromTitle( $title )->getData() ) :
				$this->parsingHelper->hasInfobox( is_null( $tmpl->text ) ? $title : $tmpl->text )
			);
	}
}
