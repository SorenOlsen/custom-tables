<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage controllers/listoffields.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\ExportTables;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;

class CustomtablesControllerListOfTables extends AdminController
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFTABLES';

	public function getModel($name = 'Tables', $prefix = 'CustomtablesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public function export()
	{
		$cIds = common::inputPost('cid', array(), 'array');
		$cIds = ArrayHelper::toInteger($cIds);

		$download_link = ExportTables::export($cIds);

		if ($download_link !== null) {
			$msg = 'COM_CUSTOMTABLES_LISTOFTABLES_N_ITEMS_EXPORTED';

			if (count($cIds) == 1)
				$msg .= '_1';

			$msg = common::translate($msg, count($cIds));

			$msg .= '&nbsp;&nbsp;<a href="' . $download_link . '" target="_blank">Download (Click Save Link As...)</a>';
		} else {
			$msg = common::translate('COM_CUSTOMTABLES_TABLES_UNABLETOEXPORT');
		}

		Factory::getApplication()->enqueueMessage($msg, 'success');

		$redirect = 'index.php?option=' . $this->option;
		$redirect .= '&view=listoftables';

		// Redirect to the item screen.
		$this->setRedirect(
			Route::_(
				$redirect, false
			)
		);
	}
}
