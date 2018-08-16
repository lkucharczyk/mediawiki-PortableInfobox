<?php

namespace PortableInfobox\Helpers;

use MediaWiki\Logger\LoggerFactory;

class PortableInfoboxParsingHelper {

	/**
	 * @desc Try to find out if infobox got "hidden" inside includeonly tag. Parse it if that's the case.
	 *
	 * @param \Title $title
	 *
	 * @return mixed false when no infoboxes found, Array with infoboxes on success
	 */
	public function parseIncludeonlyInfoboxes( $title ) {
		// for templates we need to check for include tags
		$templateText = $this->removeNowikiPre( $this->fetchArticleContent( $title ) );

		if ( $templateText ) {
			$parser = new \Parser();
			$parserOptions = new \ParserOptions();
			$frame = $parser->getPreprocessor()->newFrame();

			$includeonlyText = $parser->getPreloadText( $templateText, $title, $parserOptions );
			$infoboxes = $this->getInfoboxes( $includeonlyText );

			if ( $infoboxes ) {
				// clear up cache before parsing
				foreach ( $infoboxes as $infobox ) {
					try {
						\PortableInfoboxParserTagController::getInstance()->render( $infobox, $parser, $frame );
					} catch ( \Exception $e ) {
						LoggerFactory::getInstance( 'PortableInfobox' )->info( 'Invalid infobox syntax in includeonly tag' );
					}
				}

				return json_decode( $parser->getOutput()
					->getProperty( \PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ), true );
			}
		}

		return false;
	}

	public function reparseArticle( $title ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->parse( $this->fetchArticleContent( $title ), $title, $parserOptions );

		return json_decode( $parser->getOutput()
			->getProperty( \PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ), true );
	}

	/**
	 * @param \Title $title
	 *
	 * @return string
	 */
	protected function fetchArticleContent( \Title $title ) {
		if ( $title && $title->exists() ) {
			$wikipage = \WikiPage::factory( $title );

			if ( $wikipage && $wikipage->exists() ) {
				$content = \ContentHandler::getContentText( $wikipage->getRevision()->getContent( \Revision::RAW ) );
			}
		}

		return isset( $content ) && $content ? $content : '';
	}

	/**
	 * @param \Title $title
	 * @return string[] array of strings (infobox markups)
	 */
	public function getMarkup( \Title $title ) {
		$content = $this->fetchArticleContent( $title );
		return $this->getInfoboxes( $content );
	}

	/**
	 * @desc for given template text returns it without text in <nowiki> and <pre> tags
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected function removeNowikiPre( $text ) {
		$text = preg_replace( "/<nowiki>.+<\/nowiki>/sU", '', $text );
		$text = preg_replace( "/<pre>.+<\/pre>/sU", '', $text );

		return $text;
	}

	/**
	 * @desc From the template without <includeonly> tags, creates an array of
	 * strings containing only infoboxes. All template content which is not an infobox is removed.
	 *
	 * @param string $text Content of template which uses the <includeonly> tags
	 *
	 * @return array of striped infoboxes ready to parse
	 */
	protected function getInfoboxes( $text ) {
		preg_match_all( "/<infobox[^>]*\\/>/sU", $text, $empty );
		preg_match_all( "/<infobox.+<\/infobox>/sU", $text, $result );

		return array_merge( $empty[0], $result[0] );
	}
}
