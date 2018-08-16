<?php

namespace PortableInfobox\Helpers;

use MediaWiki\Logger\LoggerFactory;

class PortableInfoboxTemplateEngine {
	const CACHE_TTL = 86400;
	const TYPE_NOT_SUPPORTED_MESSAGE = 'portable-infobox-render-not-supported-type';

	private static $cache = [];
	private static $memcache;

	protected static $templates = [
		'wrapper' => 'PortableInfoboxWrapper.hbs',
		'title' => 'PortableInfoboxItemTitle.hbs',
		'header' => 'PortableInfoboxItemHeader.hbs',
		'media' => 'PortableInfoboxItemMedia.hbs',
		'audio' => 'PortableInfoboxItemMedia.hbs',
		'image' => 'PortableInfoboxItemMedia.hbs',
		'video' => 'PortableInfoboxItemMedia.hbs',
		'data' => 'PortableInfoboxItemData.hbs',
		'group' => 'PortableInfoboxItemGroup.hbs',
		'smart-group' => 'PortableInfoboxItemSmartGroup.hbs',
		'horizontal-group-content' => 'PortableInfoboxHorizontalGroupContent.hbs',
		'navigation' => 'PortableInfoboxItemNavigation.hbs',
		'media-collection' => 'PortableInfoboxItemMediaCollection.hbs',
		'xml-parse-error' => 'PortableInfoboxMarkupDebug.hbs'
	];

	public function __construct() {
		if ( !isset( self::$memcache ) ) {
			self::$memcache = \ObjectCache::getMainWANInstance();
		}
	}

	public static function getTemplatesDir() {
		return __DIR__ . '/../../../templates';
	}

	public static function getTemplates() {
		return self::$templates;
	}

	public function render( $type, array $data ) {
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
		if ( !empty( self::$cache[$type] ) ) {
			return self::$cache[$type];
		}

		$cachekey = self::$memcache->makeKey( __CLASS__, \PortableInfoboxParserTagController::PARSER_TAG_VERSION, $type );

		// @see https://github.com/wikimedia/mediawiki-vendor/tree/master/zordius/lightncandy
		$renderer = \LightnCandy::prepare(
			self::$memcache->getWithSetCallback( $cachekey, self::CACHE_TTL, function () use ( $type ) {
				$path = self::getTemplatesDir() . DIRECTORY_SEPARATOR . static::getTemplates()[$type];

				return \LightnCandy::compile( file_get_contents( $path ), [
					'flags' => \LightnCandy::FLAG_BESTPERFORMANCE
				] );
			} )
		);

		self::$cache[$type] = $renderer;

		return $renderer;
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
			LoggerFactory::getInstance( 'PortableInfobox' )->info( self::TYPE_NOT_SUPPORTED_MESSAGE, [ 'type' => $type ] );
		}
		return $result;
	}
}
