<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license    GNU General Public License version 3 or later; see LICENSE
 *
 * include/init.inc.php
 * @since  1.7.00
 */

defined('IN_COPPERMINE') or die('Not in Coppermine...');

// Define path to jQuery for this version of Coppermine
define('CPG_JQUERY_VERSION', 'js/jquery-1.12.4.js');
define('CPG_JQUERY_MIGRATE', 'js/jquery-migrate-1.4.1.js');

require_once 'cpg.inc.php';

// List of valid meta albums - needed for displaying 'no image to display' message
$valid_meta_albums = ['lastcom','lastcomby','lastup','lastupby','topn','toprated','lasthits','random','search','lastalb','favpics','datebrowse'];

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

// Store all reported errors in the $cpgdebugger
require_once 'include/debugger.inc.php';

// A space cannot be stored in the config table since the value field is VARCHAR, so %20 is used instead.
if ($CONFIG['keyword_separator'] == '%20') {
    $CONFIG['keyword_separator'] = ' ';
}

if ($CONFIG['log_mode']) {
    spring_cleaning('logs', (!empty($CONFIG['log_retention']) ? $CONFIG['log_retention'] : CPG_DAY * 2));
}

// Record User's IP address
$raw_ip = $superCage->server->testIp('REMOTE_ADDR') ? $superCage->server->getEscaped('REMOTE_ADDR') : '0.0.0.0';

if ($superCage->server->testIp('HTTP_CLIENT_IP')) {
    $hdr_ip = $superCage->server->getEscaped('HTTP_CLIENT_IP');
} else {
    if ($superCage->server->testIp('HTTP_X_FORWARDED_FOR')) {
        $hdr_ip = $superCage->server->getEscaped('X_FORWARDED_FOR');
    } else {
        $hdr_ip = $raw_ip;
    }
}

require_once 'include/functions.inc.php';

// Set the site_url in js_vars so that it can be used in js
set_js_var('site_url', rtrim($CONFIG['site_url'], '/'));

// Set a constant for the default theme (in the gallery config), since it might get replaced during runtime
define('DEFAULT_THEME', $CONFIG['theme']);

// Check for GD GIF Create support
if ($CONFIG['thumb_method'] == 'im' || $CONFIG['thumb_method'] == 'imx' || function_exists('imagecreatefromgif')) {
    $CONFIG['GIF_support'] = 1;
} else {
    $CONFIG['GIF_support'] = 0;
}

// Start output buffering
ob_start('cpg_filter_page_html');


// ********************************************************
// * Theme processing - start
// ********************************************************

$CONFIG['theme_config'] = DEFAULT_THEME;        // Save the gallery-configured setting

if ($matches = $superCage->get->getMatched('theme', '/^[A-Za-z0-9_-]+$/')) {
    $USER['theme'] = $matches[0];
    $hasURLtheme = true;
}
if (isset($USER['theme']) && !strstr($USER['theme'], '/') && is_dir('themes/' . $USER['theme'])) {
    $CONFIG['theme'] = strtr($USER['theme'], '$/\\:*?"\'<>|`', '____________');
} else {
    unset($USER['theme']);
}
// If no URL override, give plugins the chance to specify a theme
if (empty($hasURLtheme))
	$CONFIG['theme'] = CPGPluginAPI::filter('theme_name', $CONFIG['theme']);
if (!file_exists('themes/'.$CONFIG['theme'].'/theme.php')) {
    $CONFIG['theme'] = 'curve';
}
$THEME_DIR = 'themes/'.$CONFIG['theme'].'/';

// Load configured theme first
require 'themes/'.$CONFIG['theme'].'/theme.php';
// Then load the appropriate theme 'engine' functions and templates
if (defined('THEME_IS_V2')) {
	require('include/themes2.inc.php');
} else {
	require 'include/themes.inc.php';
}

// ********************************************************
// * Theme processing - end
// ********************************************************

if (defined('THEME_HAS_MENU_ICONS')) {
    $ICON_DIR = $THEME_DIR . 'images/icons/';
} else {
    $ICON_DIR = 'images/icons/';
}

set_js_var('icon_dir', $ICON_DIR);


// See if the fav cookie is set; else set it
if ($superCage->cookie->keyExists($CONFIG['cookie_name'] . '_fav')) {
    $FAVPICS = @unserialize(@base64_decode($superCage->cookie->getRaw($CONFIG['cookie_name'] . '_fav')));
    foreach ($FAVPICS as $key => $id ) {
        $FAVPICS[$key] = (int)$id; //protect against sql injection attacks
    }
} else {
	$FAVPICS = [];
}

// If the person is logged in get favs from DB those in the DB have precedence
if (USER_ID > 0) {
    $result = cpg_db_query("SELECT user_favpics FROM {$CONFIG['TABLE_FAVPICS']} WHERE user_id = ".USER_ID);

    $row = $result->fetchAssoc(true);
    if (!empty($row['user_favpics'])) {
        $FAVPICS = @unserialize(@base64_decode($row['user_favpics']));
    } else {
		$FAVPICS = [];
    }
}

// Include the jquery javascript library. Jquery will be included on all pages.
js_include(CPG_JQUERY_VERSION);
js_include(CPG_JQUERY_MIGRATE);

// Include the scripts.js javascript library that contains coppermine-specific
// JavaScript that is being used on all pages.
// Do not remove this line unless you really know what you're doing
js_include('js/scripts.js');

// Include the JavaScript library that takes care of the help system.
js_include('js/jquery.greybox.js');

// Include the elastic plugin for auto-expanding textareas if debug_mode is on
js_include('js/jquery.elastic.js');

// If referer is set in URL and it contains 'http' or 'script' texts then set it to 'index.php' script
/**
 * Use $CPG_REFERER wherever $_GET['referer'] is used
 */
if ( ($matches = $superCage->get->getMatched('referer', '/((\%3C)|<)[^\n]+((\%3E)|>)|(.*http.*)|(.*script.*)|(^[\W].*)/i')) ) {
    $CPG_REFERER = 'index.php';
} else {
    /**
     * Using getRaw() since we are checking the referer in the above if condition.
     */
    $CPG_REFERER = $superCage->get->getRaw('referer');
}

/**
 * CPGPluginAPI::action('page_start',null)
 *
 * Executes page_start action on all plugins
 *
 * @param null
 * @return N/A
 **/

CPGPluginAPI::action('page_start', null);

// load the main template
load_template();
$CONFIG['template_loaded'] = true;

// Remove expired bans
$now = date('Y-m-d H:i:s');
if ($CONFIG['purge_expired_bans'] == 1) {
    cpg_db_query("DELETE FROM {$CONFIG['TABLE_BANNED']} WHERE expiry < '$now'");
}
// Check if the user is banned
$user_id = USER_ID;
// Compose the query
$query_string = "SELECT null FROM {$CONFIG['TABLE_BANNED']} WHERE (";
if (USER_ID) {
    $query_string .= "user_id=$user_id OR ";
}
if ($raw_ip != $hdr_ip) {
    $query_string .= "'$raw_ip' LIKE ip_addr OR '$hdr_ip' LIKE ip_addr ";
} elseif ($raw_ip != '') {
    $query_string .= "'$raw_ip' LIKE ip_addr ";
}
$query_string .= ') AND brute_force=0 LIMIT 1';

$result = cpg_db_query($query_string);
unset($query_string);
if ($result->numRows()) {
    pageheader($lang_common['error']);
    msg_box($lang_common['information'], $lang_errors['banned']);
    pagefooter();
    exit;
}
$result->free();

// Retrieve the 'private' album set
if (!GALLERY_ADMIN_MODE && $CONFIG['allow_private_albums']) {
    get_private_album_set();
}

if (!USER_IS_ADMIN && $CONFIG['offline'] && $CPG_PHP_SELF != 'login.php' && $CPG_PHP_SELF != 'update.php') {
    pageheader($lang_errors['offline_title']);
    msg_box($lang_errors['offline_title'], $lang_errors['offline_text']);
    pagefooter();
    exit;
}
//EOF