<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	   GNU General Public License version 3 or later; see LICENSE
 *
 * include/funcs.inc.php
 * @since  1.7.03
 */

/**************************************************************************
	Database functions
 **************************************************************************/

/**
 * cpg_db_get_connection()
 *
 * Get the global database object
 *	or return a new database object
 *
 * @return
 **/

function cpg_db_get_connection($cfg=null)
{
	global $CPGDB;

	if ($cfg && isset($cfg['dbserver']) && isset($cfg['dbuser']) && isset($cfg['dbpass']) && isset($cfg['dbname'])) {
		return new CPG_Dbase($cfg);
	} else {
		return $CPGDB;
	}
}


/**
 * cpg_db_query()
 *
 * Perform a database query
 *
 * @param $query
 * @return
 **/

function cpg_db_query($query, $dbobj=null)
{
	global $CONFIG, $CPGDB, $query_stats, $queries;

	$query_start = cpgGetMicroTime();

	$result = $dbobj ? $dbobj->query($query) : $CPGDB->query($query);

	$query_end = cpgGetMicroTime();

	if (!isset($CONFIG['debug_mode']) || $CONFIG['debug_mode'] == 1 || $CONFIG['debug_mode'] == 2) {
		$trace = debug_backtrace();
		$last = $trace[0];
		$localfile = str_replace(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR , '', $last['file']);

		$duration = ($query_end - $query_start) * 1000;
		$query_stats[] = $duration;
		$queries[] = "$query [$localfile:{$last['line']}] (".round($duration, 2).' ms)';
	}

	if (!$result && !defined('UPDATE_PHP')) {
		$trace = debug_backtrace();
		$last = $trace[0];
		$localfile = str_replace(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR , '', $last['file']);
		cpg_db_error("While executing query '$query' in $localfile on line {$last['line']}");
	}

	return $result;
}


/**
 * cpg_db_error()
 *
 * Error message if a query failed
 *
 * @param $the_error
 * @return
 **/

function cpg_db_error($the_error, $dbobj=null)
{
	global $CONFIG, $CPGDB, $lang_errors, $LINEBREAK;

	$dbo = $dbobj ?: $CPGDB;

	log_write("$the_error the following error was encountered: $LINEBREAK" . $dbo->getError(), CPG_DATABASE_LOG);

	if ($CONFIG['debug_mode'] === '0' || ($CONFIG['debug_mode'] === '2' && !GALLERY_ADMIN_MODE)) {
		cpg_die(CRITICAL_ERROR, $lang_errors['database_query'], __FILE__, __LINE__);
	} else {
		$the_error .= $LINEBREAK . $LINEBREAK . 'database error: ' . $dbo->getError() . $LINEBREAK;
		$out		= '<br />' . $lang_errors['database_query'] . '.<br /><br/>
				<form name="dbsql" id="dbsql"><textarea rows="8" cols="60">' . htmlspecialchars($the_error) . '</textarea></form>';
		cpg_die(CRITICAL_ERROR, $out, __FILE__, __LINE__);
	}
}


/**
 * cpg_db_num_rows()
 *
 * Get then numbers of rows in a result
 *
 * @param $result
 * @return
 **/

function cpg_db_num_rows($result, $free=false)
{
	return $result->numRows($free);
}


/**
 * cpg_db_fetch_rowset()
 *
 * Fetch all rows in an array
 *
 * @param $result
 * @return
 **/

function cpg_db_fetch_rowset($result, $free=false)
{
	$rowset = [];

	while ( ($row = $result->fetchAssoc()) ) {
		$rowset[] = $row;
	}
	if ($free) $result->free();

	return $rowset;
}


/**
 * cpg_db_fetch_row()
 *
 * Fetch row as a simple numeric array
 *
 * @param $result
 * @return
 **/

function cpg_db_fetch_row($result, $free=false)
{
	return $result->fetchRow($free);
}


/**
 * cpg_db_fetch_assoc()
 *
 * Fetch row as an associative array
 *
 * @param $result
 * @return
 **/

function cpg_db_fetch_assoc($result, $free=false)
{
	return $result->fetchAssoc($free);
}


/**
 * cpg_db_fetch_array()
 *
 * Fetch row as both a simple and associative array
 *
 * @param $result
 * @return
 **/

function cpg_db_fetch_array($result, $free=false)
{
	return $result->fetchArray($free);
}


/**
 * cpg_db_result()
 *
 * Fetch data from a certain row and field
 *
 * @param $result
 **/

function cpg_db_result($result, $row=0, $field=0, $free=false)
{
	return $result->result($row, $field, $free);
}


/**
 * cpg_db_free_result()
 *
 * Free query result storage
 *
 * @param $result
 **/

function cpg_db_free_result($result)
{
	if (is_object($result))
		$result->free();
}


/**
 * cpg_db_last_insert_id()
 *
 * Get the last inserted id of a query
 *
 * @return integer $id
 **/

function cpg_db_last_insert_id($dbobj=null)
{
	global $CPGDB;

	return $dbobj ? $dbobj->insertId() : $CPGDB->insertId();
}

function cpg_db_insert_id($dbobj=null)
{
	return cpg_db_last_insert_id($dbobj);
}

/**
 * cpg_db_affected_rows()
 *
 * Get the count of rows affected by last query
 *
 * @return integer $id
 **/

function cpg_db_affected_rows($dbobj=null)
{
	global $CPGDB;

	return $dbobj ? $dbobj->affectedRows() : $CPGDB->affectedRows();
}


/**
 * cpg_db_escape_string()
 *
 * Escape a string for database purposes
 *
 * @param $str
 * @return
 **/

function cpg_db_escape_string($str)
{
	global $CPGDB;

	return $CPGDB->escapeStr($str);
}

function cpg_db_real_escape_string($str, $dbobj=null)
{
	global $CPGDB;

	return $dbobj ? $dbobj->escapeStr($str) : $CPGDB->escapeStr($str);
}


// Replacement for the die function

/**
 * cpg_die()
 *
 * Replacement for the die function
 *
 * @param $msg_code
 * @param $msg_text
 * @param $error_file
 * @param $error_line
 * @param boolean $output_buffer
 * @return
 **/

function cpg_die($msg_code, $msg_text,	$error_file = '?file?', $error_line = '?line?', $output_buffer = false)
{
	global $lang_common, $lang_errors, $CONFIG, $USER_DATA, $hdr_ip;

	// Three types of error levels: INFORMATION, ERROR, CRITICAL_ERROR.
	// There used to be a clumsy method for error mesages that didn't work well with i18n.
	// Let's add some more logic to this: try to get the translation
	// for the error type from the language file. If that fails, use the hard-coded
	// English string.

	// Record access denied messages to the log
	if ($msg_text == $lang_errors['access_denied'] && $CONFIG['log_mode'] != 0) {
		log_write('Denied privileged access to ' . basename($error_file) . " by user {$USER_DATA['user_name']} at IP $hdr_ip", CPG_SECURITY_LOG);
	}

	// Record invalid form token messages to the log
	if ($msg_text == $lang_errors['invalid_form_token'] && $CONFIG['log_mode'] != 0) {
		log_write('Invalid form token encountered for ' . basename($error_file) . " by user {$USER_DATA['user_name']} at IP $hdr_ip", CPG_SECURITY_LOG);
	}

	if ($msg_code == INFORMATION) {
		//$msg_icon = 'info'; not used anymore?
		$css_class = 'cpg_message_info';
		if ($lang_common['information'] != '') {
			$msg_string = $lang_common['information'];
		} else {
			$msg_string = 'Information';
		}
	} elseif ($msg_code == ERROR) {
		//$msg_icon = 'warning'; not used anymore?
		$css_class = 'cpg_message_warning';
		if ($lang_errors['error'] != '') {
			$msg_string = $lang_errors['error'];
		} else {
			$msg_string = 'Error';
		}
	} elseif ($msg_code == CRITICAL_ERROR) {
		//$msg_icon = 'stop'; not used anymore?
		$css_class = 'cpg_message_error';
		if ($lang_errors['critical_error'] != '') {
			$msg_string = $lang_errors['critical_error'];
		} else {
			$msg_string = 'Critical error';
		}
	}

	// Simple output if theme file is not loaded
	if (!function_exists('pageheader')) {
		echo 'Fatal error :<br />'.$msg_text;
		exit;
	}

	$ob = ob_get_contents();

	if ($ob) {
		ob_end_clean();
	}

	theme_cpg_die($msg_code, $msg_text, $msg_string, $css_class, $error_file, $error_line, $output_buffer, $ob);
	exit;
}



/**
 * user_get_profile()
 *
 * Decode the user profile contained in a cookie
 *
 **/

function user_get_profile()
{
	global $CONFIG, $USER;

	$superCage = Inspekt::makeSuperCage();

	/**
	 * TODO: Use the md5 # to verify integrity of cookie string
	 * At the time of installation we write a randomly generated secret salt in config.inc
	 * This secret salt will be appended to the encoded string and the resulting md5 # of this string will
	 * be appended to the encoded string with @ separator
	 * e.g. $encoded_string_with_md5 = "asdfkhasdf987we89rfadfjhasdfklj@^@".md5("asdfkhasdf987we89rfadfjhasdfklj".$secret_salt)
	 */
	if ($superCage->cookie->keyExists($CONFIG['cookie_name'].'_data')) {
		$USER = @unserialize(@base64_decode($superCage->cookie->getRaw($CONFIG['cookie_name'].'_data')));
		if (isset($USER['lang'])) {
			$USER['lang'] = strtr($USER['lang'], '$/\\:*?"\'<>|`', '____________');
		}
	}

	if (!isset($USER['ID']) || strlen($USER['ID']) != 32) {
		list($usec, $sec) = explode(' ', microtime());
		$seed = (float) $sec + ((float) $usec * 100000);
		srand((int)$seed);
		$USER = ['ID' => md5(uniqid(rand(), 1))];
	} else {
		$USER['ID'] = addslashes($USER['ID']);
	}

	if (!isset($USER['am'])) {
		$USER['am'] = 1;
	}
}


/**
 * Function to set a js var from php
 *
 * This function sets a js var in an array. This array is later converted to json string and outputted
 * in the head section of html (in theme_javascript_head function).
 * All variables which are set using this function can be accessed in js using the json object named js_vars.
 *
 * Ex: If you set a variable: set_js_var('myvar', 'myvalue')
 * then you can access it in js using : js_vars.myvar
 *
 * @param string $var Name of the variable by which the value will be accessed in js
 * @param mixed $val Value which can be string, int, array or boolean
 */
function set_js_var($var, $val)
{
	global $JS;

	// Add the variable to global array which will be used in theme_javascript_head() function
	$JS['vars'][$var] = $val;
} // function set_js_var


// Display a localised date

/**
 * localised_date()
 *
 * Display a localised date
 *
 * @param integer $timestamp
 * @param $datefmt
 * @return
 **/

function localised_date($timestamp, $datefmt)
{
    global $lang_month, $lang_day_of_week;

    $timestamp = localised_timestamp($timestamp);
    // early use (logging) may not yet have language
	if (empty($lang_day_of_week)) return date(DATE_RFC822, $timestamp);

	$dow = '\\' . implode('\\', str_split($lang_day_of_week[(int)date('w', $timestamp)]));
    $frmt = str_replace(['l','D'], '+', $datefmt);
    $mon = '\\' . implode('\\', str_split($lang_month[(int)date('m', $timestamp)-1]));
    $frmt = str_replace(['M','F'], '=', $frmt);
    $frmt = str_replace(['+','='], [$dow,$mon], $frmt);

    return date($frmt, $timestamp);
}


/**
 * localised_timestamp()
 *
 * Display a localised timestamp
 *
 * @return
 **/
function localised_timestamp($timestamp = -1)
{
	global $CONFIG;

	if ($timestamp == -1) {
		$timestamp = time();
	}

	$diff_to_GMT = date('O') / 100;

	$timestamp += ($CONFIG['time_offset'] - $diff_to_GMT) * 3600;

	return $timestamp;
}


/**
 * Get the form token and timestamp for the current user
 * this is calculated
 *
 * @return array ($timestamp, $token)
 */
function getFormToken($timestamp = null)
{
	global $raw_ip, $CONFIG;
	$superCage = Inspekt::makeSuperCage();

	if ($timestamp == null) {
		$timestamp = time();
	}

	$token_criteria_array = [
		'user_id' => USER_ID,
		'site_tkn' => $CONFIG['site_token'],
		'timestamp' => $timestamp
		];

	$token_criteria_array = CPGPluginAPI::filter('token_criteria', $token_criteria_array);

	$token_string = '';
	foreach ($token_criteria_array as $value) {
		$token_string .= $value;
	}

	$token = md5($token_string);

	return [$timestamp, $token];
}

/**
 * Checks if the form token of a request is valid
 *
 * @return boolean
 */
function checkFormToken()
{
	global $CONFIG;
	$superCage = Inspekt::makeSuperCage();

	if ($superCage->post->keyExists('form_token') || $superCage->get->keyExists('form_token')){
		// check if the token is valid
		$received_token = ($superCage->post->keyExists('form_token')) ? $superCage->post->getAlNum('form_token') : $superCage->get->getAlNum('form_token');
		$received_timestamp = ($superCage->post->keyExists('timestamp')) ? $superCage->post->getInt('timestamp') : $superCage->get->getInt('timestamp');

		//first check if the timestamp hasn't expired yet
		if ( ($received_timestamp + (int)$CONFIG['form_token_lifetime']) < time() && !defined('LOGOUT_PHP') ){
			return false;
		}

		$token = getFormToken($received_timestamp);
		if ($received_token === $token[1]) {
			return true;
		} else {
			return false;
		}
	}
	return false;
}


/**
 * cpg_load_plugin_language_file
 *
 * @param string $path
 */
function cpg_load_plugin_language_file($path) {
	global $CONFIG;
	if (file_exists('./plugins/'.$path.'/lang/english.php')) {
		$lg = 'lang_plugin_'.$path;
		global $$lg;
		include './plugins/'.$path.'/lang/english.php';
		if ($CONFIG['lang'] != 'english' && file_exists('./plugins/'.$path.'/lang/'.$CONFIG['lang'].'.php')) {
			include './plugins/'.$path.'/lang/'.$CONFIG['lang'].'.php';
		}
	}
}


function cpg_get_bridge_db_values()
{
	global $CONFIG;

	// Retrieve DB stored configuration
	$results = cpg_db_query("SELECT name, value FROM {$CONFIG['TABLE_BRIDGE']}");

	while ( ($row = $results->fetchAssoc()) ) {
		$BRIDGE[$row['name']] = $row['value'];
	} // while

	$results->free();

	return $BRIDGE;
} // function cpg_get_bridge_db_values


function replace_forbidden($str)
{
	static $forbidden_chars;
	if (!is_array($forbidden_chars)) {
		global $CONFIG, $mb_utf8_regex;
		$chars = html_entity_decode($CONFIG['forbiden_fname_char'], ENT_QUOTES, 'UTF-8');
		preg_match_all("#$mb_utf8_regex".'|[\x00-\x7F]#', $chars, $forbidden_chars);
	}
	/**
	 * $str may also come from $_POST, in this case, all &, ", etc will get replaced with entities.
	 * Replace them back to normal chars so that the str_replace below can work.
	 */
	$str = str_replace(['&amp;','&quot;','&lt;','&gt;'], ['&','"','<','>'], $str);

	$return = str_replace($forbidden_chars[0], '_', $str);

	$condition = [
		'transliteration' => true,
		'special_chars' => true
		];
	$condition = CPGPluginAPI::filter('replace_forbidden_conditions', $condition);

	/**
	 * Transliteration
	 */
	if ($condition['transliteration']) {
		require_once 'include/transliteration.inc.php';
		$return = transliteration_process($return, '_');
	}

	/**
	 * Replace special chars
	 */
	if ($condition['special_chars']) {
		$return = str_replace('%', '', rawurlencode($return));
	}

	/**
	 * Fix the obscure, misdocumented "feature" in Apache that causes the server
	 * to process the last "valid" extension in the filename (rar exploit): replace all
	 * dots in the filename except the last one with an underscore.
	 */
	// This could be concatenated into a more efficient string later, keeping it in three
	// lines for better readability for now.
	$extension = ltrim(substr($return, strrpos($return, '.')), '.');

	$filenameWithoutExtension = str_replace('.' . $extension, '', $return);

	$return = str_replace('.', '_', $filenameWithoutExtension) . '.' . $extension;

	return $return;
} // function replace_forbidden


function cpg_get_type($filename,$filter=null)
{
	global $CONFIG, $CPG_PHP_SELF;

	static $FILE_TYPES = [];

	if (!$FILE_TYPES) {

		// Map content types to corresponding user parameters
		$content_types_to_vars = [
			'image' => 'allowed_img_types',
			'audio' => 'allowed_snd_types',
			'movie' => 'allowed_mov_types',
			'document' => 'allowed_doc_types'
			];

		$result = cpg_db_query('SELECT extension, mime, content, player FROM ' . $CONFIG['TABLE_FILETYPES']);

		$CONFIG['allowed_file_extensions'] = '';

		while ( ($row = $result->fetchAssoc()) ) {
			// Only add types that are in both the database and user defined parameter
			if ($CONFIG[$content_types_to_vars[$row['content']]] == 'ALL' || is_int(strpos('/' . $CONFIG[$content_types_to_vars[$row['content']]] . '/', '/' . $row['extension'] . '/'))) {
				$FILE_TYPES[$row['extension']]		= $row;
				$CONFIG['allowed_file_extensions'] .= '/' . $row['extension'];
			} elseif ($CPG_PHP_SELF == 'displayimage.php') {
				$FILE_TYPES[$row['extension']] = $row;
			}
		}

		$CONFIG['allowed_file_extensions'] = substr($CONFIG['allowed_file_extensions'], 1);

		$result->free();
	}

	if (!is_array($filename)) {
		$filename = explode('.', $filename);
	}

	$EOA			= count($filename) - 1;
	$filename[$EOA] = strtolower($filename[$EOA]);

	if (!is_null($filter) && array_key_exists($filename[$EOA], $FILE_TYPES) && ($FILE_TYPES[$filename[$EOA]]['content'] == $filter)) {
		return $FILE_TYPES[$filename[$EOA]];
	} elseif (is_null($filter) && array_key_exists($filename[$EOA], $FILE_TYPES)) {
		return $FILE_TYPES[$filename[$EOA]];
	} else {
		return null;
	}
}

function is_image(&$file)
{
	return cpg_get_type($file, 'image');
}

function is_movie(&$file)
{
	return cpg_get_type($file, 'movie');
}

function is_audio(&$file)
{
	return cpg_get_type($file, 'audio');
}

function is_document(&$file)
{
	return cpg_get_type($file, 'document');
}

function is_flash(&$file)
{
	return pathinfo($file, PATHINFO_EXTENSION) == 'swf';
}

function is_known_filetype($file)
{
	return is_image($file) || is_movie($file) || is_audio($file) || is_document($file);
}


/**
 * function cpg_getimagesize()
 *
 * Try to get the size of an image, this is custom built as some webhosts disable this function or do weird things with it
 *
 * @param string $image
 * @param boolean $force_cpg_function
 * @return array $size
 */
function cpg_getimagesize($image, $force_cpg_function = false)
{
	if (!function_exists('getimagesize') || $force_cpg_function) {
		// custom function borrowed from http://www.wischik.com/lu/programmer/get-image-size.html
		$f = @fopen($image, 'rb');
		if ($f === false) {
			return false;
		}
		fseek($f, 0, SEEK_END);
		$len = ftell($f);
		if ($len < 24) {
			fclose($f);
			return false;
		}
		fseek($f, 0);
		$buf = fread($f, 24);
		if ($buf === false) {
			fclose($f);
			return false;
		}
		if (ord($buf[0]) == 255 && ord($buf[1]) == 216 && ord($buf[2]) == 255 && ord($buf[3]) == 224 && $buf[6] == 'J' && $buf[7] == 'F' && $buf[8] == 'I' && $buf[9] == 'F') {
			$pos = 2;
			while (ord($buf[2]) == 255) {
				if (ord($buf[3]) == 192 || ord($buf[3]) == 193 || ord($buf[3]) == 194 || ord($buf[3]) == 195 || ord($buf[3]) == 201 || ord($buf[3]) == 202 || ord($buf[3]) == 203) {
					break; // we've found the image frame
				}
				$pos += 2 + (ord($buf[4]) << 8) + ord($buf[5]);
				if ($pos + 12 > $len) {
					break; // too far
				}
				fseek($f, $pos);
				$buf = $buf[0] . $buf[1] . fread($f, 12);
			}
		}
		fclose($f);

		// GIF:
		if ($buf[0] == 'G' && $buf[1] == 'I' && $buf[2] == 'F') {
			$x = ord($buf[6]) + (ord($buf[7])<<8);
			$y = ord($buf[8]) + (ord($buf[9])<<8);
			$type = 1;
		}

		// JPEG:
		if (ord($buf[0]) == 255 && ord($buf[1]) == 216 && ord($buf[2]) == 255) {
			$y = (ord($buf[7])<<8) + ord($buf[8]);
			$x = (ord($buf[9])<<8) + ord($buf[10]);
			$type = 2;
		}

		// PNG:
		if (ord($buf[0]) == 0x89 && $buf[1] == 'P' && $buf[2] == 'N' && $buf[3] == 'G' && ord($buf[4]) == 0x0D && ord($buf[5]) == 0x0A && ord($buf[6]) == 0x1A && ord($buf[7]) == 0x0A && $buf[12] == 'I' && $buf[13] == 'H' && $buf[14] == 'D' && $buf[15] == 'R') {
			$x = (ord($buf[16])<<24) + (ord($buf[17])<<16) + (ord($buf[18])<<8) + (ord($buf[19])<<0);
			$y = (ord($buf[20])<<24) + (ord($buf[21])<<16) + (ord($buf[22])<<8) + (ord($buf[23])<<0);
			$type = 3;
		}

		// added ! from source line since it doesn't work otherwise
		if (!isset($x, $y, $type)) {
			return false;
		}
        $mime = function_exists('mime_content_type') ? mime_content_type($image) : 'image/*';
        return array($x, $y, $type, 'height="' . $x . '" width="' . $y . '"', 'mime'=>$mime);
	} else {
		$size = getimagesize($image);
		if (!$size) {
			//false was returned
			return cpg_getimagesize($image, true/*force the use of custom function*/);
		} elseif (!isset($size[0]) || !isset($size[1])) {
			//webhost possibly changed getimagesize functionality
			return cpg_getimagesize($image, true/*force the use of custom function*/);
		} else {
			//function worked as expected, return the results
			return $size;
		}
	}
} // function cpg_getimagesize

// Get the configured/available image tool class
function getImageTool ()
{
	global $CONFIG;

	if ($CONFIG['thumb_method'] == 'imx') {
		require_once 'include/imageobject_imx.class.php';
	} elseif ($CONFIG['thumb_method'] == 'im') {
		require_once 'include/imageobject_im.class.php';
	} else {
		require_once 'include/imageobject_gd.class.php';
	}
}


/**
 * Determine if an intermediate-sized picture should be used
 * The weird comparision is because only 'picture_width' is stored as config value
 *
 * @param integer $pwidth
 * @param integer $pheight
 * @return bool
 */
function cpg_picture_dimension_exceeds_intermediate_limit($pwidth, $pheight) {
	global $CONFIG;

	$resize_method = $CONFIG['picture_use'] == 'thumb' ? ($CONFIG['thumb_use'] == 'ex' ? 'any' : $CONFIG['thumb_use']) : $CONFIG['picture_use'];
	if ($resize_method == 'ht' && $pheight > $CONFIG['picture_width']) {
		return true;
	} elseif ($resize_method == 'wd' && $pwidth > $CONFIG['picture_width']) {
		return true;
	} elseif ($resize_method == 'any' && max($pwidth, $pheight) > $CONFIG['picture_width']) {
		return true;
	} else {
		return false;
	}
}


// Return the url for a picture, allows to have pictures spreaded over multiple servers
/**
 * get_pic_url()
 *
 * Return the url for a picture
 *
 * @param array $pic_row
 * @param string $mode
 * @param boolean $system_pic
 * @return string
 **/

function& get_pic_url(&$pic_row, $mode, $system_pic = false)
{
	global $CONFIG, $THEME_DIR;

	static $pic_prefix = [];
	static $url_prefix = [];

	if (!count($pic_prefix)) {
		$pic_prefix = [
			'thumb' => $CONFIG['thumb_pfx'],
			'normal' => $CONFIG['normal_pfx'],
			'orig' => $CONFIG['orig_pfx'],
			'fullsize' => ''
			];

		$url_prefix = [$CONFIG['fullpath']];
	}

	$mime_content = cpg_get_type($pic_row['filename']);

	// If $mime_content is empty there will be errors, so only perform the array_merge if $mime_content is actually an array
	if (is_array($mime_content)) {
		$pic_row = array_merge($pic_row, $mime_content);
	}

	$filepathname = null;

	// Code to handle custom thumbnails
	// If fullsize or normal mode use regular file
	if ($mime_content['content'] != 'image' && $mode == 'normal') {
		$mode = 'fullsize';
	} elseif (($mime_content['content'] != 'image' && $mode == 'thumb') || $system_pic) {

		$thumb_extensions = ['.gif','.png','.jpg'];

		// Check for user-level custom thumbnails
		// Create custom thumb path and erase extension using filename; Erase filename's extension

		if (array_key_exists('url_prefix', $pic_row)) {
			$custom_thumb_path = $url_prefix[$pic_row['url_prefix']];
		} else {
			$custom_thumb_path = '';
		}

		$custom_thumb_path .= $pic_row['filepath'] . (array_key_exists($mode, $pic_prefix) ? $pic_prefix[$mode] : '');

		$file_base_name = str_ireplace('.' . $mime_content['extension'], '', basename($pic_row['filename']));

		// Check for file-specific thumbs
		foreach ($thumb_extensions as $extension) {
			if (file_exists($custom_thumb_path . $file_base_name . $extension)) {
				$filepathname = $custom_thumb_path . $file_base_name . $extension;
				break;
			}
		}

		if (!$system_pic) {

			// Check for extension-specific thumbs
			if (is_null($filepathname)) {
				foreach ($thumb_extensions as $extension) {
					if (file_exists($custom_thumb_path . $mime_content['extension'] . $extension)) {
						$filepathname = $custom_thumb_path . $mime_content['extension'] . $extension;
						break;
					}
				}
			}

			// Check for content-specific thumbs
			if (is_null($filepathname)) {
				foreach ($thumb_extensions as $extension) {
					if (file_exists($custom_thumb_path . $mime_content['content'] . $extension)) {
						$filepathname = $custom_thumb_path . $mime_content['content'] . $extension;
						break;
					}
				}
			}
		}

		// Use default thumbs
		if (is_null($filepathname)) {

			// Check for default theme- and global-level thumbs
			$thumb_paths[] = $THEME_DIR.'images/';		// Used for custom theme thumbs
			$thumb_paths[] = 'images/thumbs/';			// Default Coppermine thumbs

			foreach ($thumb_paths as $default_thumb_path) {
				if (is_dir($default_thumb_path)) {
					if (!$system_pic) {
						foreach ($thumb_extensions as $extension) {
							// Check for extension-specific thumbs
							if (file_exists($default_thumb_path . $CONFIG['thumb_pfx'] . $mime_content['extension'] . $extension)) {
								$filepathname = $default_thumb_path . $CONFIG['thumb_pfx'] . $mime_content['extension'] . $extension;
								//thumb cropping - if we display a system thumb we calculate the dimension by any and not ex
								$pic_row['system_icon'] = true;
								break 2;
							}
						}
						foreach ($thumb_extensions as $extension) {
							// Check for media-specific thumbs (movie,document,audio)
							if (file_exists($default_thumb_path . $CONFIG['thumb_pfx'] . $mime_content['content'] . $extension)) {
								$filepathname = $default_thumb_path . $CONFIG['thumb_pfx'] . $mime_content['content'] . $extension;
								//thumb cropping
								$pic_row['system_icon'] = true;
								break 2;
							}
						}
					} else {
						// Check for file-specific thumbs for system files
						foreach ($thumb_extensions as $extension) {
							if (file_exists($default_thumb_path . $CONFIG['thumb_pfx'] . $file_base_name . $extension)) {
								$filepathname = $default_thumb_path . $CONFIG['thumb_pfx'] . $file_base_name . $extension;
								//thumb cropping
								$pic_row['system_icon'] = true;
								break 2;
							}
						} // foreach $thumb_extensions
					} // else $system_pic
				} // if is_dir($default_thumb_path)
			} // foreach $thumbpaths
		} // if is_null($filepathname)

		if ($filepathname) {
			$filepathname = path2url($filepathname);
		}
	}

	if (is_null($filepathname)) {

		$localpath = $pic_row['filepath'] . $pic_prefix[$mode] . $pic_row['filename'];

		// Check here that the filename we are going to return exists
		// If it doesn't exist we return a placeholder image
		// We then log the missing file for the admin's attention
		if (file_exists($url_prefix[$pic_row['url_prefix']] . $localpath)) {
			$filepathname = $url_prefix[$pic_row['url_prefix']] . path2url($localpath);
		} else {
			$filepathname = 'images/thumbs/thumb_nopic.png';
			$pic_row['system_icon'] = true;
			if ($CONFIG['log_mode'] != 0) {
				log_write("File {$url_prefix[$pic_row['url_prefix']]}$localpath is missing.");
			}
		}
	}

	// Added hack:	"&& !isset($pic_row['mode'])" thumb_data filter isn't executed for the fullsize image
	if ($mode == 'thumb' && !isset($pic_row['mode'])) {
		$pic_row['url']	 = $filepathname;
		$pic_row['mode'] = $mode;

		$pic_row = CPGPluginAPI::filter('thumb_data', $pic_row);
	} elseif ($mode != 'thumb') {
		$pic_row['url']	 = $filepathname;
		$pic_row['mode'] = $mode;
	} else {
		$pic_row['url'] = $filepathname;
	}

	$pic_row = CPGPluginAPI::filter('picture_url', $pic_row);

	return $pic_row['url'];
} // function get_pic_url


// Function to create correct URLs for image name with space or exotic characters
/**
 * path2url()
 *
 * Function to create correct URLs for image name with space or exotic characters
 *
 * @param $path
 * @return
 **/

function path2url($path)
{
	return str_replace('%2F', '/', rawurlencode($path));
}

/**
 * Rewritten by Nathan Codding - Feb 6, 2001. Taken from phpBB code
 * - Goes through the given string, and replaces xxxx://yyyy with an HTML <a> tag linking
 *         to that URL
 * - Goes through the given string, and replaces www.xxxx.yyyy[zzzz] with an HTML <a> tag linking
 *         to http://www.xxxx.yyyy[/zzzz]
 * - Goes through the given string, and replaces xxxx@yyyy with an HTML mailto: tag linking
 *                to that email address
 * - Only matches these 2 patterns either after a space, or at the beginning of a line
 *
 * Notes: the email one might get annoying - it's easy to make it more restrictive, though.. maybe
 * have it require something like xxxx@yyyy.zzzz or such. We'll see.
 */

/**
 * make_clickable()
 *
 * @param $text
 * @return
 **/

function make_clickable($text)
{
    $ret = ' '.$text;

    $ret = preg_replace("#([\n ])([a-z]+?)://([a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]+)#i", "\\1<a href=\"\\2://\\3\" rel=\"external\">\\2://\\3</a>", $ret);
    $ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\" rel=\"external\">www.\\2.\\3\\4</a>", $ret);
    $ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

    return substr($ret, 1);
}


/**
 * filter_content()
 *
 * Replace strings that match badwords with tokens indicating it has been filtered.
 *
 * @param string or array $str
 * @return string or array
 **/
function filter_content($str)
{
    global $lang_bad_words, $CONFIG, $ercp;

    if ($CONFIG['filter_bad_words']) {

        static $ercp = array();

        if (!count($ercp)) {
            foreach ($lang_bad_words as $word) {
                $ercp[] = '/' . ($word[0] == '*' ? '': '\b') . str_replace('*', '', $word) . ($word[(strlen($word)-1)] == '*' ? '': '\b') . '/i';
            }
        }

        if (is_array($str)) {

            $new_str = array();

            foreach ($str as $key => $element) {
                $new_str[$key] = filter_content($element);
            }

            $str = $new_str;

        } else {
            $stripped_str = strip_tags($str);
            $str          = preg_replace($ercp, '(...)', $stripped_str);
        }
    }
    return $str;
} // function filter_content



//EOF