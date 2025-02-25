<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\Fields;
use CustomTables\MySQLWhereClause;

class updateFiles
{
	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function process(): array
	{
		$ct = new CT;

		$stepsize = (int)common::inputGetInt('stepsize', 10);
		$startindex = (int)common::inputGetInt('startindex', 0);

		$old_typeparams = base64_decode(common::inputGetBase64('old_typeparams', ''));
		if ($old_typeparams == '')
			return array('error' => 'old_typeparams not set');

		$old_params = JoomlaBasicMisc::csv_explode(',', $old_typeparams);

		$new_typeparams = base64_decode(common::inputGetBase64('new_typeparams', ''));
		if ($new_typeparams == '')
			return array('error' => 'new_typeparams not set');

		$new_params = JoomlaBasicMisc::csv_explode(',', $new_typeparams);

		$fieldid = (int)common::inputGetInt('fieldid', 0);
		if ($fieldid == 0)
			return array('error' => 'fieldid not set');

		$fieldrow = Fields::getFieldRow($fieldid);

		$ct->getTable($fieldrow->tableid);

		$count = 0;
		if ($startindex == 0) {
			$count = updateFiles::countFiles($ct->Table->realtablename, $fieldrow->realfieldname, $ct->Table->realidfieldname);
			if ($stepsize > $count)
				$stepsize = $count;
		}

		$status = updateFiles::processFiles($ct, $fieldrow, $old_params, $new_params);

		return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startindex, 'stepsize' => $stepsize, 'error' => $status);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function countFiles($realtablename, $realfieldname, $realidfieldname): int
	{
		//$query = 'SELECT count(' . $realidfieldname . ') AS c FROM ' . $realtablename . ' WHERE ' . $realfieldname . ' IS NOT NULL AND ' . $realfieldname . ' != ""';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($realfieldname, null, 'NOT NULL');
		$whereClause->addCondition($realfieldname, '', '!=');

		$rows = database::loadAssocList($realtablename, ['count(' . $realidfieldname . ') AS c'], $whereClause, null, null);
		return (int)$rows[0]['c'];
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected static function processFiles(CT &$ct, $fieldrow, array $old_params, array $new_params): ?string
	{
		//$query = 'SELECT ' . implode(',', $ct->Table->selects) . ' FROM ' . $ct->Table->realtablename . ' WHERE '
		//. $fieldrow->realfieldname . ' IS NOT NULL AND ' . $fieldrow->realfieldname . ' != ""';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition($fieldrow->realfieldname, null, 'NOT NULL');
		$whereClause->addCondition($fieldrow->realfieldname, '', '!=');

		$rows = database::loadAssocList($ct->Table->realtablename, $ct->Table->selects, $whereClause, null, null);

		foreach ($rows as $file) {
			$field_row_old = (array)$fieldrow;
			$field_row_old['params'] = $old_params;

			$field_old = new Field($ct, $field_row_old, $file);
			$field_old->params = $old_params;
			$field_old->parseParams($file, $field_old->type);

			$old_FileFolder = CT_FieldTypeTag_file::getFileFolder($field_old->params[1]);

			$old_FileFolder = str_replace('/', DIRECTORY_SEPARATOR, $old_FileFolder);

			$field_row_new = (array)$fieldrow;

			$field_new = new Field($ct, $field_row_new, $file);
			$field_new->params = $new_params;
			$field_new->parseParams($file, $field_old->type);

			$new_FileFolder = CT_FieldTypeTag_file::getFileFolder($field_new->params[1]);

			$new_FileFolder = str_replace('/', DIRECTORY_SEPARATOR, $new_FileFolder);

			$status = updateFiles::processFile($file[$fieldrow->realfieldname], $old_FileFolder, $new_FileFolder);
			//if $status is null then all good, status is a text string with error message if any
			if ($status !== null)
				return $status;
		}

		JoomlaBasicMisc::deleteFolderIfEmpty($old_FileFolder);
		return null;
	}

	protected static function processFile($filename, $old_FileFolder, $new_FileFolder): ?string
	{
		$filepath_old = JPATH_SITE . $old_FileFolder . DIRECTORY_SEPARATOR . $filename;
		$filepath_new = JPATH_SITE . $new_FileFolder . DIRECTORY_SEPARATOR . $filename;

		if (file_exists($filepath_old)) {
			if ($filepath_old != $filepath_new) {
				if (!@rename($filepath_old, $filepath_new))
					return 'cannot move file to ' . $filepath_new;
			}
		} else
			return 'file "' . $old_FileFolder . DIRECTORY_SEPARATOR . $filename . '" not found';

		return null;
	}
}
