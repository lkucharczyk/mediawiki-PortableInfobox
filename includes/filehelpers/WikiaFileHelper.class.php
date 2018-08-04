<?php

class WikiaFileHelper {
	/**
	 * Format duration from second to h:m:s
	 * @param integer $sec
	 * @return string $hms
	 */
	public static function formatDuration( $sec ) {
		$sec = intval( $sec );

		$format = ( $sec >= 3600 ) ? 'H:i:s' : 'i:s';
		$hms = gmdate( $format, $sec );

		return $hms;
	}

	/**
	 * Get file from title (Please be careful when using $force)
	 *
	 * Note: this method turns a string $title into an object, affecting the calling code version
	 * of this variable
	 *
	 * @param Title|string $title
	 * @return File|null $file
	 */
	public static function getFileFromTitle( &$title ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title, NS_FILE );
		}

		if ( $title instanceof Title ) {
			$file = wfFindFile( $title );
			if ( $file instanceof File && $file->exists() ) {
				return $file;
			}
		}

		return null;
	}
}
