<?php
/**
 * This file contains classes with static helper functions for other classes.
 *
 * @file
 * @author Niklas Laxström
 * @author Winston Sung
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;

/**
 * Essentially random collection of helper functions, similar to GlobalFunctions.php.
 */
class PortableInfoboxUtils {
	/**
	 * Add support for <= 1.31. Wrapper method to fetch the ContentLanguage
	 * @return string
	 */
	public static function getContentLanguage(): object {
		if ( method_exists( MediaWikiServices::getInstance(), 'getContentLanguage') ) {
			return MediaWikiServices::getInstance()->getContentLanguage();
		}

		global $wgContLang;
		return $wgContLang;
	}

	/**
	 * Add support for <= 1.34. Wrapper method to fetch the MW version
	 * @return string
	 */
	public static function getMWVersion(): string {
		if ( defined( 'MW_VERSION' ) ) {
			return MW_VERSION;
		}

		global $wgVersion;
		return $wgVersion;
	}
}
