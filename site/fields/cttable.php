<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
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

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

$versionObject = new Version;
$version = (int)$versionObject->getShortVersion();

trait JFormFieldCTTableCommon
{
	protected static function getOptionList(): array
	{
		//$query = 'SELECT id,tablename FROM #__customtables_tables WHERE published=1 ORDER BY tablename';
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('published', 1);

		$tables = database::loadObjectList('#__customtables_tables',
			['id', 'tablename'], $whereClause, 'tablename');

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT')];

		if ($tables) {
			foreach ($tables as $table)
				$options[] = HTMLHelper::_('select.option', $table->tablename, $table->tablename);
		}
		return $options;
	}
}

if ($version < 4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTTable extends JFormFieldList
	{
		use JFormFieldCTTableCommon;

		protected $type = 'CTTable';

		protected function getOptions()//$name, $value, &$node, $control_name)
		{
			return self::getOptionList();
		}
	}
} else {
	class JFormFieldCTTable extends FormField
	{
		use JFormFieldCTTableCommon;

		public $type = 'CTTable';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList();
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}