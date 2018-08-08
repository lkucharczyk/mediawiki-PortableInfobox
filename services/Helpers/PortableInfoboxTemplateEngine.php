<?php

namespace Wikia\PortableInfobox\Helpers;

use MediaWiki\Logger\LoggerFactory;
use Wikia\Template\TemplateEngine;

class PortableInfoboxTemplateEngine {
	const TYPE_NOT_SUPPORTED_MESSAGE = 'portable-infobox-render-not-supported-type';

	protected static $templates = [
		'wrapper' => 'PortableInfoboxWrapper.hbs',
		'title' => 'PortableInfoboxItemTitle.hbs',
		'header' => 'PortableInfoboxItemHeader.hbs',
		'image' => 'PortableInfoboxItemImage.hbs',
		'data' => 'PortableInfoboxItemData.hbs',
		'group' => 'PortableInfoboxItemGroup.hbs',
		'smart-group' => 'PortableInfoboxItemSmartGroup.hbs',
		'horizontal-group-content' => 'PortableInfoboxHorizontalGroupContent.hbs',
		'navigation' => 'PortableInfoboxItemNavigation.hbs',
		'image-collection' => 'PortableInfoboxItemImageCollection.hbs'
	];
	protected $templateEngine;

	public function __construct() {
		$this->templateEngine = ( new TemplateEngine )
			->setPrefix( self::getTemplatesDir() );
	}

	public static function getTemplatesDir() {
		return dirname( __FILE__ ) . '/../../templates';
	}

	public static function getTemplates() {
		return self::$templates;
	}

	public function render( $type, array $data ) {
		return $this->templateEngine->clearData()
			->setData( $data )
			->render( self::getTemplates()[ $type ] );
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
