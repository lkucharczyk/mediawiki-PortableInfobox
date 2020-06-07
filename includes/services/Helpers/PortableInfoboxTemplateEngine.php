<?php

namespace PortableInfobox\Helpers;

use LightnCandy\LightnCandy;
use MediaWiki\MediaWikiServices;
use MediaWiki\Logger\LoggerFactory;


class PortableInfoboxTemplateEngine {
	const CACHE_TTL = 86400;
	const TYPE_NOT_SUPPORTED_MESSAGE = 'portable-infobox-render-not-supported-type';
	const COMPILE_FLAGS = class_exists( 'LightnCandy\LightnCandy' ) ?
		LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_PARENT :
		\LightnCandy::FLAG_BESTPERFORMANCE | \LightnCandy::FLAG_PARENT;

	private static $cache = [];
	private static $memcache;

	protected static $templates = [
		'wrapper' => 'PortableInfoboxWrapper.hbs',
		'title' => 'PortableInfoboxItemTitle.hbs',
		'header' => 'PortableInfoboxItemHeader.hbs',
		'media' => 'PortableInfoboxItemMedia.hbs',
		'data' => 'PortableInfoboxItemData.hbs',
		'group' => 'PortableInfoboxItemGroup.hbs',
		'smart-group' => 'PortableInfoboxItemSmartGroup.hbs',
		'horizontal-group-content' => 'PortableInfoboxHorizontalGroupContent.hbs',
		'navigation' => 'PortableInfoboxItemNavigation.hbs',
		'media-collection' => 'PortableInfoboxItemMediaCollection.hbs',
		'panel' => 'PortableInfoboxPanel.hbs',
		'xml-parse-error' => 'PortableInfoboxMarkupDebug.hbs'
	];

	public function __construct() {
		if ( !isset( self::$memcache ) ) {
			self::$memcache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		}
	}

	public static function getTemplatesDir() {
		return __DIR__ . '/../../../templates';
	}

	public static function getTemplates() {
		return self::$templates;
	}

	public function render( $type, array $data ) {
		global $wgPortableInfoboxUseHeadings;
		$data['useHeadings'] = $wgPortableInfoboxUseHeadings;

		$renderer = $this->getRenderer( $type );
		return $renderer( $data );
	}

	/**
	 * Returns a template renderer
	 *
	 * @param string $type Template type
	 * @return Closure
	 */
	public function getRenderer( $type ) {
		global $wgPortableInfoboxCacheRenderers;

		if ( empty( self::$cache[$type] ) ) {
			$path = self::getTemplatesDir() . DIRECTORY_SEPARATOR . static::getTemplates()[$type];

			$lightnCandyClass = class_exists( 'LightnCandy\LightnCandy' ) ?
				\LightnCandy : LightnCandy;
			if ( $wgPortableInfoboxCacheRenderers ) {
				$cachekey = self::$memcache->makeKey(
					__CLASS__, \PortableInfoboxParserTagController::PARSER_TAG_VERSION, $type
				);
				$template = self::$memcache->getWithSetCallback(
					$cachekey, self::CACHE_TTL, function () use ( $path ) {
						// @see https://github.com/wikimedia/mediawiki-vendor/tree/master/zordius/lightncandy
						return $lightnCandyClass::compile( file_get_contents( $path ), [
							'flags' => self::COMPILE_FLAGS
						] );
					}
				);
			} else {
				$template = $lightnCandyClass::compile( file_get_contents( $path ), [
					'flags' => self::COMPILE_FLAGS
				] );
			}

			self::$cache[$type] = $lightnCandyClass::prepare( $template );
		}

		return self::$cache[$type];
	}

	/**
	 * check if item type is supported and logs unsupported types
	 *
	 * @param string $type - template type
	 *
	 * @return bool
	 */
	public static function isSupportedType( $type ) {
		$result = isset( static::getTemplates()[$type] );
		if ( !$result ) {
			LoggerFactory::getInstance( 'PortableInfobox' )->info(
				self::TYPE_NOT_SUPPORTED_MESSAGE, [ 'type' => $type ]
			);
		}
		return $result;
	}
}
