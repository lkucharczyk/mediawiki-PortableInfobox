<?php

class PortableInfoboxHooks {

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModuleStyles( 'ext.PortableInfobox.styles' );
		$out->addModules( 'ext.PortableInfobox.scripts' );

		return true;
	}

	public static function onBeforePageDisplayMobile( OutputPage $out, Skin $skin ) {
		$out->addModuleStyles( 'ext.PortableInfobox.styles.mobile' );
		$out->addModules( 'ext.PortableInfobox.scripts.mobile' );

		return true;
	}

	public static function onWgQueryPages( &$queryPages = [ ] ) {
		$queryPages[] = [ 'AllinfoboxesQueryPage', 'AllInfoboxes' ];

		return true;
	}
	public static function onBeforeParserrenderImageGallery ( $parser, $gallery ) {
		if ( $gallery instanceof ImageGalleryBase ) {
			PortableInfobox\Helpers\PortableInfoboxDataBag::getInstance()->setGallery(
				Parser::MARKER_PREFIX . "-gallery-" . sprintf( '%08X', $parser->mMarkerIndex-1 ) . Parser::MARKER_SUFFIX,
				$gallery
			);
		}

		return true;
	}

	public static function onAllInfoboxesQueryRecached() {
		$cache = ObjectCache::getMainWANInstance();
		$cache->delete( $cache->makeKey( __CLASS__, ApiQueryAllinfoboxes::MCACHE_KEY ) );

		return true;
	}

	/**
	 * Purge memcache before edit
	 *
	 * @param $article Page|WikiPage
	 * @param $user
	 * @param $text
	 * @param $summary
	 * @param $minor
	 * @param $watchthis
	 * @param $sectionanchor
	 * @param $flags
	 * @param $status
	 *
	 * @return bool
	 */
	public static function onPageContentSave( Page $article, User $user, &$text, &$summary, $minor, $watchthis, $sectionanchor, &$flags, Status &$status ): bool {
		PortableInfoboxDataService::newFromTitle( $article->getTitle() )->delete();

		return true;
	}

	/**
	 * Purge memcache, this will not rebuild infobox data
	 *
	 * @param Page|WikiPage $article
	 *
	 * @return bool
	 */
	public static function onArticlePurge( Page $article ) {
		PortableInfoboxDataService::newFromTitle( $article->getTitle() )->purge();

		return true;
	}

	/**
	 * Insert a newly created infobox into querycache, and purge the list of
	 * infoboxes.
	 *
	 * @param  Page     $page          The created page object
	 * @param  User     $user          The user who created the page
	 * @param  string   $text          Text of the new article
	 * @param  string   $summary       Edit summary
	 * @param  int      $minoredit     Minor edit flag
	 * @param  boolean  $watchThis     Whether or not the user should watch the page
	 * @param  null     $sectionAnchor Not used, set to null
	 * @param  int      $flags         Flags for this page
	 * @param  Revision $revision      The newly inserted revision object
	 * @return boolean
	 */
	public static function onPageContentInsertComplete( Page $page, User $user, $text, $summary, $minoredit,
	                                                    $watchThis, $sectionAnchor, &$flags, Revision $revision ) {
		$title = $page->getTitle();
		if ( $title->inNamespace( NS_TEMPLATE ) ) {
			( new AllinfoboxesQueryPage() )->addTitleToCache( $title );
		}

		return true;
	}
}