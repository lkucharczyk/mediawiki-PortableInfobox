<?php

class PortableInfoboxHooks {

	public static function onWgQueryPages( &$queryPages = [] ) {
		$queryPages[] = [ 'AllinfoboxesQueryPage', 'AllInfoboxes' ];

		return true;
	}

	public static function onBeforeParserrenderImageGallery( $parser, $gallery ) {
		if ( $gallery instanceof ImageGalleryBase ) {
			PortableInfobox\Helpers\PortableInfoboxDataBag::getInstance()->setGallery(
				Parser::MARKER_PREFIX . "-gallery-" . sprintf( '%08X', $parser->mMarkerIndex - 1 ) . Parser::MARKER_SUFFIX,
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
	 * @param Page|WikiPage $article
	 *
	 * @return bool
	 */
	public static function onPageContentSave( Page $article ) {
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
}
