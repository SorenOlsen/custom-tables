<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomtablesHelper;
use JHtml;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

use CustomTables\Fields;

class ListOfFields
{
    var CT $ct;
    var int $tableid;
    var string $tablename;
    var string $tabletitle;
    //var $languages;
    var array $items;
    var string $editLink;

    var bool $canState;
    var bool $canDelete;
    var bool $canEdit;
    var bool $saveOrder;

    function __construct(CT $ct, int $tableid, string $tablename, string $tabletitle, array $items, bool $canState, bool $canDelete, bool $canEdit, bool $saveOrder)
    {
        $this->ct = $ct;
        $this->tableid = $tableid;
        $this->tablename = $tablename;
        $this->tabletitle = $tabletitle;
        //$this->languages = $languages;
        $this->items = $items;
        $this->editLink = "index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid=" . $this->tableid;

        $this->canState = $canState;
        $this->canDelete = $canDelete;
        $this->canEdit = $canEdit;
        $this->saveOrder = $saveOrder;
    }

    public function renderBody(): string
    {
        $result = '';

        foreach ($this->items as $i => $item) {
            $canCheckin = $this->ct->Env->user->authorise('core.manage', 'com_checkin') || $item->checked_out == $this->ct->Env->user->id || $item->checked_out == 0;
            $userChkOut = Factory::getUser($item->checked_out);

            $result .= $this->renderBodyLine($item, $i, $canCheckin, $userChkOut);
        }

        return $result;
    }

    protected function renderBodyLine(object $item, int $i, $canCheckin, $userChkOut): string
    {
        $result = '<tr class="row' . ($i % 2) . '" data-draggable-group="' . $this->tableid . '">';

        if ($this->canState or $this->canDelete) {
            $result .= '<td class="text-center">';

            if ($item->checked_out) {
                if ($canCheckin)
                    $result .= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname);
                else
                    $result .= '&#9633;';
            } else
                $result .= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->fieldname);

            $result .= '</td>';
        }

        if ($this->canEdit) {
            $result .= '<td class="text-center d-none d-md-table-cell">';

            $iconClass = '';
            if (!$this->saveOrder)
                $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');

            $result .= '<span class="sortable-handler' . $iconClass . '"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>';

            if ($this->saveOrder)
                $result .= '<input type="text" name="order[]" size="5" value="' . $item->ordering . '" class="width-20 text-area-order hidden">';

            $result .= '</td>';
        }

        $result .= '<td><div class="name">';

        if ($this->canEdit) {
            $result .= '<a href="' . $this->editLink . '&id=' . $item->id . '">' . $this->escape($item->fieldname) . '</a>';
            if ($item->checked_out)
                $result .= JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoffields.', $canCheckin);
        } else
            $result .= $this->escape($item->fieldname);

        if ($this->customtablename != '')
            $result .= '<br/><span style="color:grey;">' . $this->customtablename . '.' . $item->customfieldname . '</span>';

        $result .= '</div></td>';

        $result .= '<td><div class="name"><ul style="list-style: none !important;margin-left:0;padding-left:0;">';

        $item_array = (array)$item;
        $moreThanOneLang = false;

        foreach ($this->ct->Languages->LanguageList as $lang) {
            $fieldTitle = 'fieldtitle';
            $fieldDescription = 'description';
            if ($moreThanOneLang) {
                $fieldTitle .= '_' . $lang->sef;
                $fieldDescription .= '_' . $lang->sef;

                if (!array_key_exists($fieldTitle, $item_array)) {
                    Fields::addLanguageField('#__customtables_fields', 'fieldtitle', $fieldTitle);
                    $item_array[$fieldTitle] = '';
                }

                if (!array_key_exists($fieldTitle, $item_array)) {
                    Fields::addLanguageField('#__customtables_fields', 'description', $fieldDescription);
                    $item_array[$fieldDescription] = '';
                }
            }

            $result .= '<li>' . (count($this->ct->Languages->LanguageList) > 1 ? $lang->title . ': ' : '') . '<b>' . $this->escape($item_array[$fieldTitle]) . '</b></li>';

            $moreThanOneLang = true; //More than one language installed
        }

        $result .= '
                        </ul>
                    </div>
                </td>';

        $result .= '<td>' . Text::_($item->typeLabel) . '</td>';
        $result .= '<td>' . $this->escape($item->typeparams) . $this->checkTypeParams($item->type, $item->typeparams) . '</td>';
        $result .= '<td>' . Text::_($item->isrequired) . '</td>';
        $result .= '<td>' . $this->escape($item->tabletitle) . '</td>';
        $result .= '<td class="text-center btns d-none d-md-table-cell">';
        if ($this->canState) {
            if ($item->checked_out) {
                if ($canCheckin)
                    $result .= JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb');
                else
                    $result .= JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb');

            } else {

                $result .= JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', true, 'cb');
            }
        } else {
            $result .= JHtml::_('jgrid.published', $item->published, $i, 'listoffields.', false, 'cb');
        }
        $result .= '</td>';

        $result .= '<td class="d-none d-md-table-cell">' . $item->id . '</td>';
        $result .= '</tr>';

        return $result;
    }

    public function escape($var)
    {

        if (strlen($var) > 50) {
            // use the helper htmlEscape method instead and shorten the string
            return CustomtablesHelper::htmlEscape($var, $this->_charset, true);
        }
        // use the helper htmlEscape method instead.
        return CustomtablesHelper::htmlEscape($var, $this->_charset);
    }

    protected function checkTypeParams(string $type, string $typeparams): string
    {
        if ($type == 'sqljoin' or $type == 'records') {
            $params = \JoomlaBasicMisc::csv_explode(',', $typeparams, '"', false);

            $error = [];

            if ($params[0] == '')
                $error[] = 'Join Table not selected';

            if (!isset($params[1]) or $params[1] == '')
                $error[] = 'Join Field not selected';

            return '<br/><p class="alert-error">' . implode(', ', $error) . '</p>';
        }
        return '';
    }
}