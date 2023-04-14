<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	   GNU General Public License version 3 or later; see LICENSE
 *
 * include/cpg.inc.php
 * @since  1.7.03
 */

define('COPPERMINE_VERSION', '1.7.02');
define('COPPERMINE_VERSION_STATUS', 'beta');
// Define path to jQuery for this version of Coppermine
//define('CPG_JQUERY_VERSION', 'js/jquery-1.12.4.js');

defined('IN_COPPERMINE') or die('Not in Coppermine...');

function cpgGetMicroTime()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}
$cpg_time_start = cpgGetMicroTime();

// Set a flag if register globals is on to show a warning to admin
if (ini_get('register_globals') == '1' || strtolower(ini_get('register_globals')) == 'on') {
	$register_globals_flag = true;
} else {
	$register_globals_flag = false;
}

require_once 'inspekt.php';

// Set $strict to false to make the superglobals available
$strict = TRUE;

$superCage = Inspekt::makeSuperCage($strict);
// Remove any variables introduced by register_globals, if enabled
$keysToSkip = ['keysToSkip', 'register_globals_flag', 'superCage', 'cpg_time_start', 'key'];

if ($register_globals_flag && is_array($GLOBALS)) {
	foreach ($GLOBALS as $key => $value) {
		if (!in_array($key, $keysToSkip) && isset($$key)) {
			unset($$key);
		}
	}
}

// HTML tags replace pairs (used at some places for input validation)
$HTML_SUBST = [
	'&' => '&amp;',
	'"' => '&quot;',
	'<' => '&lt;',
	'>' => '&gt;',
	'%26' => '&amp;',
	'%22' => '&quot;',
	'%3C' => '&lt;',
	'%3E' => '&gt;',
	'%27' => '&#39;',
	'\'' => '&#39;'
	];



// used for timing purposes
$query_stats = [];
$queries = [];

// Initialise the $CONFIG array and some other variables
$CONFIG = [];

$PHP_SELF = '';

$possibilities = ['REDIRECT_URL','PHP_SELF','SCRIPT_URL','SCRIPT_NAME','SCRIPT_FILENAME'];

foreach ($possibilities as $test) {
	if ( ($matches = $superCage->server->getMatched($test, '/([^\/]+\.php)$/')) ) {
		$CPG_PHP_SELF = $matches[1];
		break;
	}
}
/**
 * TODO: $REFERER has a potential for exploitation as the QUERY_STRING is being fetched with getRaw()
 * A probable solution is to parse the query string into its individual key and values and check
 * them against a regex, recombine and use only if all the values are safe else set referer to index.php
 */
$REFERER			= urlencode($CPG_PHP_SELF . (($superCage->server->keyExists('QUERY_STRING') && $superCage->server->getRaw('QUERY_STRING')) ? '?' . $superCage->server->getRaw('QUERY_STRING') : ''));
$ALBUM_SET			= '';
$META_ALBUM_SET		= '';
$FORBIDDEN_SET		= '';
$FORBIDDEN_SET_DATA = [];
$CURRENT_CAT_NAME	= '';
$CAT_LIST			= '';
$LINEBREAK			= "\r\n"; // For compatibility both on Windows as well as *nix

// Define some constants
define('USER_GAL_CAT', 1);
define('FIRST_USER_CAT', 10000);
define('TEMPLATE_FILE', 'template.html');
// Constants used by the cpg_die function
define('INFORMATION', 1);
define('ERROR', 2);
define('CRITICAL_ERROR', 3);

// Include config and functions files
if (file_exists('include/config.inc.php')) {
	ob_start();
	require_once 'config.inc.php';
	ob_clean();
} else {
	// error handling: if the config file doesn't exist go to install
	die('<html>
	<head>
		<title>Coppermine not installed yet</title>
		<meta http-equiv="refresh" content="10;url=install.php" />
		<style type="text/css">
		<!--
		body { font-size: 12px; background: #FFFFFF; margin: 20%; color: black; font-family: verdana, arial, helvetica, sans-serif;}
		-->
		</style>
	</head>
	<body>
		<img src="images/coppermine-logo.png" alt="Coppermine Photo Gallery - Your Online Photo Gallery" /><br />
		Coppermine Photo Gallery seems not to be installed correctly, or you are running coppermine for the first time. You\'ll be redirected to the installer. If your browser doesn\'t support redirect, click <a href="install.php">here</a>.
	</body>
</html>');
}

$mb_utf8_regex = '[\xE1-\xEF][\x80-\xBF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xC2-\xDF][\x80-\xBF]';

require_once 'funcs.inc.php';

// Include logger functions
include_once 'include/logger.inc.php';

// see http://php.net/mbstring for details
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding('UTF-8');
}

$CONFIG['TABLE_PICTURES']		= $CONFIG['TABLE_PREFIX'].'pictures';
$CONFIG['TABLE_ALBUMS']			= $CONFIG['TABLE_PREFIX'].'albums';
$CONFIG['TABLE_COMMENTS']		= $CONFIG['TABLE_PREFIX'].'comments';
$CONFIG['TABLE_CATEGORIES']		= $CONFIG['TABLE_PREFIX'].'categories';
$CONFIG['TABLE_CONFIG']			= $CONFIG['TABLE_PREFIX'].'config';
$CONFIG['TABLE_USERGROUPS']		= $CONFIG['TABLE_PREFIX'].'usergroups';
$CONFIG['TABLE_VOTES']			= $CONFIG['TABLE_PREFIX'].'votes';
$CONFIG['TABLE_USERS']			= $CONFIG['TABLE_PREFIX'].'users';
$CONFIG['TABLE_BANNED']			= $CONFIG['TABLE_PREFIX'].'banned';
$CONFIG['TABLE_EXIF']			= $CONFIG['TABLE_PREFIX'].'exif';
$CONFIG['TABLE_FILETYPES']		= $CONFIG['TABLE_PREFIX'].'filetypes';
$CONFIG['TABLE_ECARDS']			= $CONFIG['TABLE_PREFIX'].'ecards';
$CONFIG['TABLE_FAVPICS']		= $CONFIG['TABLE_PREFIX'].'favpics';
$CONFIG['TABLE_BRIDGE']			= $CONFIG['TABLE_PREFIX'].'bridge';
$CONFIG['TABLE_VOTE_STATS']		= $CONFIG['TABLE_PREFIX'].'vote_stats';
$CONFIG['TABLE_HIT_STATS']		= $CONFIG['TABLE_PREFIX'].'hit_stats';
$CONFIG['TABLE_TEMP_MESSAGES']	= $CONFIG['TABLE_PREFIX'].'temp_messages';
$CONFIG['TABLE_CATMAP']			= $CONFIG['TABLE_PREFIX'].'categorymap';
$CONFIG['TABLE_LANGUAGE']		= $CONFIG['TABLE_PREFIX'].'languages';
$CONFIG['TABLE_DICT']			= $CONFIG['TABLE_PREFIX'].'dict';

// Connect to database
list($db_ext, $db_sub) = explode(':', $CONFIG['dbtype'].':');
$db_ext = $db_ext ?: 'mysqli';
require 'database/'.$db_ext.'/dbase.inc.php';
$CPGDB = new CPG_Dbase($CONFIG);

if (!$CPGDB->isConnected()) {
	log_write('Unable to connect to database: ' . $CPGDB->getError(false,true), CPG_DATABASE_LOG);
	die('<strong>Coppermine critical error</strong>:<br />Unable to connect to database !<br /><br />'.$CPGDB->db_type.' said: <strong>' . $CPGDB->getError(false,true) . '</strong>');
}

// Retrieve DB stored configuration
$result = cpg_db_query("SELECT name, value FROM {$CONFIG['TABLE_CONFIG']}");
if (!$result) cpg_db_error('When read CONFIG from database ');
while ( ($row = $result->fetchAssoc()) ) {
	$CONFIG[$row['name']] = $row['value'];
} // while
$result->free();

// Set a constant for the default language (in the gallery config), since it might get replaced during runtime
define('DEFAULT_LANGUAGE', $CONFIG['lang']);

// ********************************************************
// * Language processing --- start
// ********************************************************

require 'lang/english.php';						// Load the default language file: 'english.php'
$CONFIG['lang_config'] = DEFAULT_LANGUAGE;		// Save the gallery-configured setting
$CONFIG['default_lang'] = $CONFIG['lang'];		// Save default language

$enabled_languages_array = [];

$result = cpg_db_query("SELECT lang_id FROM {$CONFIG['TABLE_LANGUAGE']} WHERE enabled='YES'");
while ($row = $result->fetchAssoc()) {
	$enabled_languages_array[] = $row['lang_id'];
}
$result->free();

// Process language selection if present in URI or in user profile or try
// autodetection if default charset is utf-8
if ($matches = $superCage->get->getMatched('lang', '/^[a-z0-9_-]+$/')) {
	$USER['lang'] = $matches[0];
}

// Set the user preference to the language submit by URL parameter or by auto-detection
// Only set the preference if a corresponding file language file exists.
if (isset($USER['lang']) && !strstr($USER['lang'], '/') && file_exists('lang/' . $USER['lang'] . '.php')) {
	$CONFIG['lang'] = strtr($USER['lang'], '$/\\:*?"\'<>|`', '____________');
} elseif ($CONFIG['charset'] == 'utf-8' && $CONFIG['language_autodetect'] != 0) {
	include 'include/select_lang.inc.php';
	if (file_exists('lang/' . $USER['lang'] . '.php') == TRUE) {
		if (in_array($USER['lang'], $enabled_languages_array)) {
			$CONFIG['lang'] = $USER['lang'];
		}
	}
} else {
	unset($USER['lang']);
}

if (!file_exists("lang/{$CONFIG['lang']}.php")) {
	$CONFIG['lang'] = 'english';
}

// We finally load the chosen language file if it differs from English
if ($CONFIG['lang'] != 'english') {
	require 'lang/' . $CONFIG['lang'] . '.php';
}
set_js_var('lang_close', $lang_common['close']);
if (defined('THEME_HAS_MENU_ICONS')) {
	set_js_var('icon_close_path', $THEME_DIR . 'images/icons/close.png');
} else {
	set_js_var('icon_close_path', 'images/icons/close.png');
}

// ********************************************************
// * Language processing --- end
// ********************************************************

// Include plugin API
require 'plugin_api.inc.php';
if ($CONFIG['enable_plugins'] == 1) {
	CPGPluginAPI::load();
}

// Check if Coppermine is allowed to store cookies (cookie consent is required and user has agreed to store cookies)
define('CPG_COOKIES_ALLOWED', ($CONFIG['cookies_need_consent'] && !$superCage->cookie->keyExists($CONFIG['cookie_name'].'_cookies_allowed') ? false : true));

// Reference 'site_url' to 'ecards_more_pic_target'
$CONFIG['site_url'] =& $CONFIG['ecards_more_pic_target'];


// Set UDB_INTEGRATION if enabled in admin
if ($CONFIG['bridge_enable'] == 1 && !defined('BRIDGEMGR_PHP')) {
	$BRIDGE = cpg_get_bridge_db_values();
} else {
	$BRIDGE['short_name'] = 'coppermine';
	$BRIDGE['recovery_logon_failures'] = 0;
	$BRIDGE['use_post_based_groups'] = false;
}

define('UDB_INTEGRATION', $BRIDGE['short_name']);

require_once 'bridge/' . UDB_INTEGRATION . '.inc.php';

// Parse cookie stored user profile
user_get_profile();

// Authenticate
$cpg_udb->authenticate();

// Test if admin mode
$USER['am'] = isset($USER['am']) ? (int)$USER['am'] : 0;
define('GALLERY_ADMIN_MODE', USER_IS_ADMIN && $USER['am']);
define('USER_ADMIN_MODE', USER_ID && USER_CAN_CREATE_ALBUMS && !GALLERY_ADMIN_MODE);

require_once 'include/debugger.inc.php';
// Set error logging level
// Maze's new error report system
if (!USER_IS_ADMIN) {
	if (!$CONFIG['debug_mode']) {
		$cpgdebugger->stop(); // useless to run debugger because there's no output
	}
	if (!CPG_DVL) error_reporting(0); // hide all errors for visitors
}

$USER_DATA['allowed_albums'] = [];

if (!GALLERY_ADMIN_MODE) {
	$result = cpg_db_query("SELECT aid FROM {$CONFIG['TABLE_ALBUMS']} WHERE moderator_group IN ".USER_GROUP_SET);
	if ($result->numRows()) {
		while ( ($row = $result->fetchAssoc()) ) {
			$USER_DATA['allowed_albums'][] = $row['aid'];
		}
	}
	$result->free();
}

// Set the debug flag to be used in js var
if ($CONFIG['debug_mode'] == 1 || ($CONFIG['debug_mode'] == 2 && GALLERY_ADMIN_MODE)) {
	set_js_var('debug', true);
} else {
	set_js_var('debug', false);
}

//EOF