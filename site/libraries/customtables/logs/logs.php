<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use Exception;

trait Logs
{
	public function saveLog($listing_id, int $action): void
	{
		// Actions:
		// 1 - New
		// 2 - Edit
		// 3 - Publish
		// 4 - Unpublish
		// 5 - Delete
		// 6 - Image Uploaded
		// 7 - Image Deleted
		// 8 - File Uploaded
		// 9 - File Deleted

		//$sets = array();
		$data = [];
		$data ['userid'] = (int)$this->Env->user->id;
		$data ['datetime'] = ['NOW()', 'sanitized'];
		$data ['tableid'] = $this->tableid;
		$data ['listingid'] = (int)$listing_id;
		$data ['action'] = (int)$action;
		$data ['Itemid'] = (int)common::inputGetInt('Itemid', 0);
		/*$sets[] = (int)$this->Env->user->id;
		$sets[] = 'NOW()';
		$sets[] = (int)$this->tableid;
		$sets[] = (int)$listing_id;
		$sets[] = (int)$action;
		$sets[] = (int)common::inputGetInt('Itemid', 0);*/

		//Value from sets
		//$fields = ['userid', 'datetime', 'tableid', 'listingid', 'action', 'Itemid'];
		//$query = 'INSERT INTO #__customtables_log (' . implode(',', $fields) . ') VALUES (' . implode(',', $sets) . ')';

		try {
			database::insert('#__customtables_log', $data);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
