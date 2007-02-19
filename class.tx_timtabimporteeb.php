<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Ingo Renner (typo3@ingo-renner.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * class.tx_timtabimporteeb.php
 *
 * Class for converting a ee_blog weblog to timtab
 * $Id$
 *
 * @author Ingo Renner <typo3@ingo-renner.com>
 */

 
class tx_timtabimporteeb {
	
	var $host;
	var $db;
	var $user;
	var $pass;
	
	var $postsPID;
	var $categoriesPID;
	var $commentsPID;
	
	var $prefix;
	
	/**
	 * initializes values for the database connection
	 * 
	 * @param	string		Database host
	 * @param	string		Database
	 * @param	string		Username
	 * @param	string		Password
	 * @param	integer		posts PID
	 * @param	integer		categories PID
	 * @param	integer		comments PID
	 * @param	string		Table Prefix
	 * @return	void
	 */
	function init($host, $db, $user, $pass, $postsPID, $catsPID, $commPID, $prefix = '') {
		$this->host          = $host;
		$this->db            = $db;
		$this->user          = $user;
		$this->pass          = $pass;
	
		$this->postsPID      = $postsPID;
		$this->categoriesPID = $catsPID;
		$this->commentsPID   = $commPID;
	
		$this->prefix        = $prefix;
	}
	
	/**
	 * checks how many records are found and returns a little statistics as HTML
	 * 
	 * @return	string		HTML
	 */
	function query() {
		
		$import = t3lib_div::makeInstance('t3lib_db');
		$import->link = $import->sql_pconnect(
			$this->host,
			$this->user,
			$this->pass
		);
		$import->sql_select_db($this->db);
		$postCommCount = $import->exec_SELECTgetRows(
			'entry_type, COUNT(*) AS num',
			'tx_eeblog_maintable',
			'deleted = 0',
			'entry_type',
			'entry_type ASC'
		);
		$catCount = $import->exec_SELECTgetRows(
			'COUNT(*) AS num',
			'tx_eeblog_categories',
			'deleted = 0'
		);
		
		$content  = 'Found <strong>'.$postCommCount[0]['num'].' Posts</strong> in<br />';
		$content .= '<strong>'.$catCount[0]['num'].' Categories</strong> with a sum of<br />';
		$content .= '<strong>'.$postCommCount[1]['num'].' Comments</strong>.<br /><br />';
				
		return $content;
	}
	
	/**
	 * performs the actual import
	 * 
	 * @return	boolean		true on successful import, false otherwise
	 */
	function import() {
		$import = t3lib_div::makeInstance('t3lib_db');
		$import->link = $import->sql_pconnect(
			$this->host,
			$this->user,
			$this->pass
		);
		$import->sql_select_db($this->db);
		
		$posts = $import->exec_SELECTgetRows(
			'*',
			'tx_eeblog_maintable',
			'deleted = 0 AND entry_type = 0',
			'',
			'',
			'',
			'uid'
		);
		if($import->sql_error()) {
			return false;	
		}
				
		$categories = $import->exec_SELECTgetRows(
			'*',
			'tx_eeblog_categories',
			'deleted = 0',
			'',
			'',
			'',
			'uid'
		);
		if($import->sql_error()) {
			return false;	
		}
		
		$postsCatsRel = $import->exec_SELECTgetRows(
			'*',
			'tx_eeblog_maintable_categories_mm',
			''
		);
		if($import->sql_error()) {
			return false;	
		}
		
		$comments = $import->exec_SELECTgetRows(
			'*',
			'tx_eeblog_maintable',
			'deleted = 0 AND entry_type = 1',
			'',
			'',
			'',
			'uid'
		);
		if($import->sql_error()) {
			return false;	
		}
		
		//import posts
		foreach($posts as $key => $row) {
			$insertFields = array(
				'pid'          => $this->postsPID,
				'tstamp'       => $row['tstamp'],
				'crdate'       => $row['crdate'],
				'hidden'       => $row['hidden'],
				'starttime'    => $row['starttime'],
				'endtime'      => $row['endtime'],
				'title'        => $row['subject'],
				'datetime'     => $row['crdate'],
				'image'        => $row['image'],
				'imagecaption' => $row['imagecaption'],
				'bodytext'     => $row['message'],
				'author'       => $row['author'],
				'author_email' => $row['email'],
				'type'         => 3,
				'cruser_id'    => $row['cruser_id']
			);
			
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tt_news',
				$insertFields
			);
			if(!$res) {
				return false;	
			}
			$posts[$key]['new_uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			unset($res);
		}
		unset($key, $row);
		
		//importing categories
		foreach($categories as $key => $row) {
			$insertFields = array(
				'pid'         => $this->categoriesPID,
				'tstamp'      => $row['tstamp'],				
				'crdate'      => $row['crdate'],
				'title'       => $row['name'],
				'hidden'      => $row['hidden'],
				'sorting'     => $row['sorting'],
				'description' => $row['description']
			);
			
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tt_news_cat',
				$insertFields
			);
			if(!$res) {
				return false;	
			}
			$categories[$key]['new_uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			unset($res);
		}
		unset($key, $row);
		
		//importing posts <-> category relations
		foreach($postsCatsRel as $key => $row) {
			$insertFields = array(
				'uid_local'   => $posts[$row['uid_local']]['new_uid'],
				'uid_foreign' => $categories[$row['uid_foreign']]['new_uid'],
				'sorting'     => $row['sorting']
			);
			
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tt_news_cat_mm',
				$insertFields
			);
			if(!$res) {
				return false;	
			}
			unset($res);	
		}
		
		//importing comments
		foreach($comments as $key => $row) {
			$insertFields = array(
				'pid'         => $this->commentsPID,
				'uid_tt_news' => $posts[$row['parent']]['new_uid'],
				'tstamp'      => $row['tstamp'],
				'crdate'      => $row['crdate'],
				'cruser_id'   => $row['cruser_id'],
				'hidden'      => $row['hidden'],
				'firstname'   => $row['author'],
				'email'       => $row['email'],
				'homepage'    => $row['subject'],
				'entry'       => $row['message']
			);
			
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_veguestbook_entries',
				$insertFields
			);
			if(!$res) {
				return false;	
			}
			$comments[$key]['new_uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			unset($res);
		}
		unset($key, $row);

		return true;				
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_import_eeb/class.tx_timtabimporteeb.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_import_eeb/class.tx_timtabimporteeb.php']);
}

?>