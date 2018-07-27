<?php

/** @defgroup nirvana Nirvana
 *  The Nirvana Framework
 */

/**
 * Nirvana Framework - Application class
 *
 * @ingroup nirvana
 *
 * @author Adrian 'ADi' Wieczorek <adi(at)wikia-inc.com>
 * @author Owen Davis <owen(at)wikia-inc.com>
 * @author Wojciech Szela <wojtek(at)wikia-inc.com>
 * @author Federico "Lox" Lucignano <federico(at)wikia-inc.com>
 */
class WikiaApp {

	/**
	 * WikiaApp is a singleton
	 * @var WikiaApp
	 */
	protected static $appInstance;

	/**
	 * global MW variables helper accessor
	 * @var $wg WikiaGlobalRegistry
	 */
	public $wg = null;

	/**
	 * constructor
	 * @param WikiaRegistry $globalRegistry
	 */

	public function __construct( WikiaRegistry $globalRegistry = null ) {
		if(!is_object($globalRegistry)) {
			$globalRegistry = (new WikiaGlobalRegistry);
		}

		// set helper accessors
		$this->wg = $globalRegistry;

		if ( !is_object( $this->wg ) ) {
			// can't use Wikia::log or wfDebug or wfBacktrace at this point (not defined yet)
			error_log( __METHOD__ . ': WikiaGlobalRegistry not set in ' . __CLASS__ . ' ' . __METHOD__ );
			$message = "";
			$bt = debug_backtrace();
			foreach ($bt as $t) {
 				$message .= $t['function'] . "() in " . basename($t['file']) . ":" . $t['line'] . " / ";
			}
			error_log( __METHOD__ . ': ' . $message );
		}
	}

	/**
	 * get application object
	 * @return WikiaApp
	 */
	public static function app() {
		if (!isset(self::$appInstance)) {
			self::$appInstance = new WikiaApp();
		}
		return self::$appInstance;
	}
	
	/**
	 * Check if the skin is the one specified, useful to fork parser logic on a per-skin base
	 *
	 * @author Federico "Lox" Lucignano <federico(at)wikia-inc.com>
	 *
	 * @param mixed $skinName the skin short name (e.g. 'oasis' or 'wikiamobile') or an array of those
	 * @param object $skinObj [optional] a subclass of Skin, Linker or DummyLinker to be checked,
	 *        useful in hooks that have a skin instance as the paramenter, the global user skin
	 *        will be used if not passed
	 *
	 * @example
	 * $this->app->checkSkin( 'oasis' ); //single check against the global instance, in a WikiaObject subclass
	 * F::app()->checkSkin( 'oasis' ); //single check against the global instance
	 * F::app()->checkSkin( array( 'oasis', 'wikiamobile' ) ); //multiple checkagainst the global instance
	 * F::app()->checkSkin( 'monobook', $skinObj ); //e.g. in a hook that passes the skin instance as a parameter
	 *
	 * @return bool whether the skin is the one (or one of) specified
	 */
	public function checkSkin( $skinName, $skinObj = null ) {
		//wfProfileIn( __METHOD__ );
		$skinNames = null;
		$res = null;

		if ( is_string( $skinName ) ) {
			$skinNames = array( $skinName );
		} elseif ( is_array( $skinName ) ) {
			$skinNames = $skinName;
		} else {
			$res = false;
		}

		if ( is_null( $res ) ) {
			//MW 1.19 upgrade fix (FB#29972), hooks don't pass always a descendant of Skin or Linker,
			//so check if the passed in object actually has the required method
			if ( method_exists( $skinObj, 'getSkinName' ) ) {
				$skin = $skinObj;
			} else {
				//MW 1.19 upgrade fix, the global reference to the skin is not in the
				//User object anymore, use RequestContext::getSkin instead
				$skin = RequestContext::getMain()->getSkin();
			}

			$res = in_array( $skin->getSkinName(), $skinNames );
		}

		//wfProfileOut( __METHOD__ );
		return $res;
	}
}

/**
 * WikiaFactory class alias
 *
 */
class F extends WikiaApp { }

