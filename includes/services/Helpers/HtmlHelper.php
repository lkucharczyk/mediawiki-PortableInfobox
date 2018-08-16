<?php

namespace PortableInfobox\Helpers;

// original class & authors: https://github.com/Wikia/app/blob/dev/includes/wikia/helpers/HtmlHelper.class.php
class HtmlHelper {

	/**
	 * Creates properly encoded DOMDocument. Silent loadHTML errors
	 * as libxml treats for example <figure> as invalid tag
	 *
	 * @param string $html
	 *
	 * @return DOMDocument
	 */
	public static function createDOMDocumentFromText( $html ) {
		$error_setting = libxml_use_internal_errors( true );
		$document = new \DOMDocument();

		if ( !empty( $html ) ) {
			//encode for correct load
			$document->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		}

		// clear user generated html parsing errors
		libxml_clear_errors();
		libxml_use_internal_errors( $error_setting );

		return $document;
	}
}
