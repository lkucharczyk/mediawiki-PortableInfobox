<?php
namespace PortableInfobox\Parser;

class MediaWikiParserService implements ExternalParser {

	protected $parser;
	protected $frame;
	protected $localParser;
	protected $tidyDriver;

	public function __construct( \Parser $parser, \PPFrame $frame ) {
		global $wgTidyConfig;

		$this->parser = $parser;
		$this->frame = $frame;

		if ( $wgTidyConfig !== null ) {
			$this->tidyDriver = \MWTidy::factory( array_merge( $wgTidyConfig, [ 'pwrap' => false ] ) );
		}
	}

	/**
	 * Method used for parsing wikitext provided in infobox that might contain variables
	 *
	 * @param string $wikitext
	 *
	 * @return string HTML outcome
	 */
	public function parseRecursive( $wikitext ) {
		$parsed = $this->parser->internalParse( $wikitext, false, $this->frame );
		if ( in_array( substr( $parsed, 0, 1 ), [ '*', '#' ] ) ) {
			//fix for first item list elements
			$parsed = "\n" . $parsed;
		}
		$output = $this->parser->doBlockLevels( $parsed, false );
		$ready = $this->parser->mStripState->unstripBoth( $output );
		$this->parser->replaceLinkHolders( $ready );
		if ( isset( $this->tidyDriver ) ) {
			$ready = $this->tidyDriver->tidy( $ready );
		}
		$newlinesstripped = preg_replace( '|[\n\r]|Us', '', $ready );
		$marksstripped = preg_replace( '|{{{.*}}}|Us', '', $newlinesstripped );

		return $marksstripped;
	}

	public function replaceVariables( $wikitext ) {
		$output = $this->parser->replaceVariables( $wikitext, $this->frame );

		return $output;
	}

	/**
	 * Add image to parser output for later usage
	 *
	 * @param string $title
	 */
	public function addImage( $title ) {
		$file = wfFindFile( $title );
		$tmstmp = $file ? $file->getTimestamp() : false;
		$sha1 = $file ? $file->getSha1() : false;
		$this->parser->getOutput()->addImage( $title, $tmstmp, $sha1 );
	}
}
