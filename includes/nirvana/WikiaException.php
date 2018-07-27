<?php

use Wikia\Logger\WikiaLogger;

/**
 * WikiaBaseException
 *
 * it is used by WikiaException
 *
 * @ingroup nirvana
 *
 * @author Jakub Olek <jakubolek@wikia-inc.com>
 */
abstract class WikiaBaseException extends MWException {
	/**
	 * Overrides MWException::report to also write exceptions to error_log
	 *
	 * @see  MWException::report
	 */
	function report() {
		$file = $this->getFile();
		$line = $this->getLine();
		$message = $this->getMessage();
		$url = '[no URL]';

		trigger_error( "Exception from line {$line} of {$file}: {$message} ({$url})", E_USER_ERROR );

		/*
		bust the headers_sent check in MWException::report()
		Uncomment to override normal MWException headers
		in order to display an error page instead of a 500 error
		WARNING: Varnish doesn't like those
		flush();
		*/
		parent::report();
	}
}

/**
 * Base exception class for the Nirvana framework
 *
 * @ingroup nirvana
 *
 * @author Wojciech Szela <wojtek@wikia-inc.com>
 * @author Federico "Lox" Lucignano <federico@wikia-inc.com>
 * @link http://pl2.php.net/manual/en/class.exception.php
 */
class WikiaException extends WikiaBaseException {
	public function __construct($message = '', $code = 0, Exception $previous = null) {
		global $wgRunningUnitTests;
		parent::__construct( $message, $code, $previous );

		if (!$wgRunningUnitTests) {
			$exceptionClass = get_class($this);

			// log on devboxes to /tmp/debug.log
			wfDebug($exceptionClass . ": {$message}\n");
			wfDebug($this->getTraceAsString());

			WikiaLogger::instance()->error($exceptionClass, [
				'err' => $message,
				'errno' => $code,
				'exception' => $previous instanceof Exception ? $previous : $this,
			]);
		}
	}
}