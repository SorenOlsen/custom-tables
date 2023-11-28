<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\DataTypes\Tree;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\String\StringHelper;

jimport('joomla.application.component.view');

class CustomTablesViewList extends HtmlView
{
	var $_name = 'list';

	function display($tpl = null)
	{
		$mainframe = Factory::getApplication();
		$this->_layout = 'default';

		$this->limitstart = common::inputGetInt('limitstart', '0');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->lists = $this->_getViewLists();

		// Ensure ampersands and double quotes are encoded in item titles
		foreach ($this->items as $i => $item) {
			$treename = $item->treename;
			$treename = JFilterOutput::ampReplace($treename);
			$treename = str_replace('"', '&quot;', $treename);
			$this->items[$i]->treename = $treename;
		}

		//Ordering allowed ?
		$this->ordering = ($this->lists['order'] == 'm.ordering');

		HTMLHelper::_('behavior.tooltip');

		parent::display($tpl);
	}

	function &_getViewLists()
	{
		$mainframe = Factory::getApplication();
		$context = 'com_customtables.list.';

		$filter_order = $mainframe->getUserStateFromRequest($context . "filter_order", 'filter_order', 'm.ordering', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($context . "filter_order_Dir", 'filter_order_Dir', 'ASC', 'word');

		$filterRootParent = $mainframe->getUserStateFromRequest($context . "filter_rootparent", 'filter_rootparent', '', 'int');

		$levelLimit = $mainframe->getUserStateFromRequest($context . "levellimit", 'levellimit', 10, 'int');
		$search = $mainframe->getUserStateFromRequest($context . "search", 'search', '', 'string');
		$search = StringHelper::strtolower($search);

		// level limit filter
		$lists['levellist'] = HTMLHelper::_('select.integerlist', 1, 20, 1, 'levellimit', 'size="1" onchange="document.adminForm.submit();"', $levelLimit);


		// Category List
		$javascript = 'onchange="document.adminForm.submit();"';

		$available_rootparents = Tree::getAllRootParents();
		$lists['rootparent'] = HTMLHelper::_('select.genericlist', $available_rootparents, 'filter_rootparent', $javascript, 'id', 'optionname', $filterRootParent);

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;


		$lists['order'] = $filter_order;

		// search filter
		$lists['search'] = $search;

		return $lists;
	}
}
