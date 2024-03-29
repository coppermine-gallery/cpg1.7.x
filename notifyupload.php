<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	GNU General Public License version 3 or later; see LICENSE
 *
 * notifyupload.php
 * @since  1.7.03
 */

define('IN_COPPERMINE', true);
define('DB_INPUT_PHP', true);

require 'include/cpg.inc.php';
require 'include/mailer.inc.php';

// NOTE: This script won't make any noise in any case.

$category = false; // Setting category to false to begin with.

// The script must get called as a AJAX request and with the data we are expecting
if ($CONFIG['upl_notify_admin_email'] && $superCage->json->keyExists('album') && $superCage->json->getInt('album')) {

    $album = $superCage->json->getInt('album');

    if (!GALLERY_ADMIN_MODE) {
        $result = cpg_db_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='$album' and (uploads = 'YES' OR category = '" . (USER_ID + FIRST_USER_CAT) . "' OR owner = '" . USER_ID . "')");
        if ($result->numRows()) {
            $row = $result->fetchArray(true);
            $category = $row['category'];
        }
    } else {
        $result = cpg_db_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='$album'");
        if ($result->numRows()) {
            $row = $result->fetchArray(true);
            $category = $row['category'];
        }
    }

    if (false !== $category) {
        // Test if picture requires approval
        if (GALLERY_ADMIN_MODE) {
            $approved = 'YES';
        } elseif (!$USER_DATA['priv_upl_need_approval'] && $category == FIRST_USER_CAT + USER_ID) {
            $approved = 'YES';
        } elseif (!$USER_DATA['pub_upl_need_approval'] && $category < FIRST_USER_CAT) {
            $approved = 'YES';
        } else {
            $approved = 'NO';
        }

        $PIC_NEED_APPROVAL = ($approved == 'NO');

        if ($PIC_NEED_APPROVAL) {
            cpg_mail(
            	'admin',
            	sprintf($lang_db_input_php['notify_admin_email_subject'], $CONFIG['gallery_name']),
            	make_clickable(
            		sprintf(
        				$lang_db_input_php['notify_admin_email_body'],
        				USER_NAME,
        				$CONFIG['ecards_more_pic_target']
        					.(substr($CONFIG["ecards_more_pic_target"], -1) == '/' ? '' : '/')
        					.'editpics.php?mode=upload_approval'
            		)
            	)
            );
        }
    }
}
//EOF