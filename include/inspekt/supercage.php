<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	GNU General Public License version 3 or later; see LICENSE
 *
 * include/inspekt/supercage.php
 * @since  1.7.03
 */

/**
 * Inspekt Supercage
 *
 * @author Ed Finkler <coj@funkatron.com>
 *
 * @package Inspekt
 */

defined('IN_COPPERMINE') or die('Not in Coppermine...');

/**
 * require main Inspekt class
 */
require_once 'include/inspekt.php';

/**
 * require the Cage class
 */
require_once 'include/inspekt/cage.php';

/**
 * The Supercage object wraps ALL of the superglobals
 *
 */
Class Inspekt_Supercage {

	/**
	 * The get cage
	 *
	 * @var Inspekt_Cage
	 */
	var $get;

	/**
	 * The post cage
	 *
	 * @var Inspekt_Cage
	 */
	var $post;

	/**
	 * The cookie cage
	 *
	 * @var Inspekt_Cage
	 */
	var $cookie;

	/**
	 * The env cage
	 *
	 * @var Inspekt_Cage
	 */
	var $env;

	/**
	 * The json cage
	 *
	 * @var Inspekt_Cage
	 */
	var $json;

	/**
	 * The files cage
	 *
	 * @var Inspekt_Cage
	 */
	var $files;

	/**
	 * The session cage
	 *
	 * @var Inspekt_Cage
	 */
	var $session;

	var $server;

	/**
	 * Enter description here...
	 *
	 * @return Inspekt_Supercage
	 */
	function __construct() {
		// placeholder
	}

	/**
	 * Enter description here...
	 *
	 * @param boolean $strict
	 * @return Inspekt_Supercage
	 */
	public static function Factory($strict = TRUE) {

		$sc	= new Inspekt_Supercage();
		$sc->_makeCages($strict);

		// eliminate the $_REQUEST superglobal
		if ($strict) {
			$_REQUEST = null;
		}

		return $sc;

	}

	/**
	 * Enter description here...
	 *
	 * @see Inspekt_Supercage::Factory()
	 * @param boolean $strict
	 */
	function _makeCages($strict=TRUE) {
		$this->get = Inspekt::makeGetCage($strict);
		$this->post	= Inspekt::makePostCage($strict);
		$this->cookie = Inspekt::makeCookieCage($strict);
		$this->env = Inspekt::makeEnvCage($strict);
		$this->json	= Inspekt::makeJsonCage($strict);
		$this->files = Inspekt::makeFilesCage($strict);
        /**
         * Don't put session in cage as it will nullify $_SESSION and we will loose the session completely.
         * TODO: Find a way to put the session data in cage and still retain the session correctly
         */
		//$this->session = Inspekt::makeSessionCage($strict);
		$this->server = Inspekt::makeServerCage($strict);
	}

}