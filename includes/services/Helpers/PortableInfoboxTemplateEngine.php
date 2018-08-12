<?php

namespace PortableInfobox\Helpers;

use MediaWiki\Logger\LoggerFactory;

class PortableInfoboxTemplateEngine {
	const TYPE_NOT_SUPPORTED_MESSAGE = 'portable-infobox-render-not-supported-type';

	private static $cache = [];

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
		'xml-parse-error' => 'PortableInfoboxMarkupDebug.hbs'
	];
	
	public function __construct() {}

	public static function getTemplatesDir() {
		return dirname( __FILE__ ) . '/../../../templates';
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
	 * @param $type string Template type
	 * @return Closure
	 */
	public function getRenderer( $type ) {
		if ( !empty( self::$cache[ $type ] ) ) {
			return self::$cache[ $type ];
		}

		$path = self::getTemplatesDir() . DIRECTORY_SEPARATOR . self::$templates[ $type ];

		// @see https://github.com/wikimedia/mediawiki-vendor/tree/master/zordius/lightncandy
		$renderer = \LightnCandy::prepare(
			\LightnCandy::compile( file_get_contents( $path ), [
				'flags' => \LightnCandy::FLAG_BESTPERFORMANCE
			] )
		);

		self::$cache[ $type ] = $renderer;

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
		$result = isset( static::$templates[ $type ] );
		if ( !$result ) {
			LoggerFactory::getInstance( 'PortableInfobox' )->info( self::TYPE_NOT_SUPPORTED_MESSAGE, [ 'type' => $type ] );
		}
		return $result;
	}
}
