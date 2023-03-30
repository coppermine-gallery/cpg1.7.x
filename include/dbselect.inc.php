<?php
/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	   GNU General Public License version 3 or later; see LICENSE
 *
 * include/dbselect.inc.php
 * @since  1.7.00
 */

defined('IN_COPPERMINE') or die('Not in Coppermine...');

class DbaseSelect
{
	protected $dbtypes = array('mysqli'=>'MYSQLI','pdo:mysql'=>'PDO:MYSQL');

	public function __construct ($dbtypes=null)
	{
		if ($dbtypes) $this->dbtypes = $dbtypes;
	}

	public function options ($sel='mysqli', $upd=false)
	{
		$opts = '';
		foreach ($this->dbtypes as $dtype => $dsp) {
			$opts .= '<option value="'.$dtype.'"';
			list($tnam,$tsub) = explode(':', $dtype.':');
			require_once 'include/database/'.$tnam.'/install.php';
			$ifunc = 'dbcheck_'.$tnam;
			if (function_exists($ifunc) && $ifunc($tsub)===true) {
				if ($dtype == $sel) $opts .= ' selected';
				$opts .= '>'.$dsp;
			} else {
				$opts .= ' disabled>'.$dsp.' ('.$ifunc($tsub).')';
			}
			$opts .= '</option>';
		}
		return $opts;
	}

}
//EOF