<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

use CustomTables\common;
use CustomTables\database;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldESEmailLayout extends JFormFieldList
{
	protected $type = 'esemaillayout';

	protected function getOptions()
	{
		$path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path . 'loader.php');
		CTLoader();

		$query = 'SELECT id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename FROM #__customtables_layouts'
			. ' WHERE published=1 AND layouttype=7'
			. ' ORDER BY tablename,layoutname';

		$messages = database::loadObjectList((string)$query);
		$options = array();

		$options[] = JHtml::_('select.option', '', '- ' . common::translate('COM_CUSTOMTABLES_SELECT'));

		if ($messages) {
			foreach ($messages as $message)
				$options[] = JHtml::_('select.option', $message->layoutname, $message->tablename . ': ' . $message->layoutname);
		}
		return $options;
	}
}
