<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.application.component.controlleradmin');

use CustomTables\CT;
use CustomTables\Fields;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

class CustomtablesControllerListOfFields extends JControllerAdmin
{
    protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFFIELDS';

    public function checkin($model = null)
    {
        $tableid = $this->input->get('tableid', 0, 'int');
        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoffields&tableid=' . (int)$tableid;

        $cid = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);
        $count = count($cid);

        $db = Factory::getDBO();

        foreach ($cid as $id) {
            $query = 'UPDATE #__customtables_fields SET checked_out=0, checked_out_time=NULL WHERE id=' . $id;
            $db->setQuery($query);
            $db->execute();
        }

        if ($count == 1)
            $msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN';
        elseif ($count == 0)
            $msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_0';
        else
            $msg = 'COM_CUSTOMTABLES_N_ITEMS_CHECKED_IN_MORE';

        Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg, $count), 'success');

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }

    public function getModel($name = 'Fields', $prefix = 'CustomtablesModel', $config = array())
    {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function publish()
    {
        if ($this->task == 'publish')
            $status = 1;
        elseif ($this->task == 'unpublish')
            $status = 0;
        elseif ($this->task == 'trash')
            $status = -2;
        else
            return;

        $tableid = $this->input->get('tableid', 0, 'int');

        if ($tableid != 0) {
            $table = ESTables::getTableRowByID($tableid);
            if (!is_object($table) and $table == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                return;
            } else {
                $tablename = $table->tablename;
            }
        }

        $cid = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);

        $ok = true;

        foreach ($cid as $id) {
            if ((int)$id != 0) {
                $id = (int)$id;
                $isok = $this->setPublishStatusSingleRecord($id, $status);
                if (!$isok) {
                    $ok = false;
                    break;
                }
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoffields&tableid=' . (int)$tableid;

        if ($this->task == 'trash')
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_TRASHED';
        elseif ($this->task == 'publish')
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_PUBLISHED';
        else
            $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_UNPUBLISHED';

        if (count($cid) == 1)
            $msg .= '_1';

        Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg, count($cid)), 'success');

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }

    protected function setPublishStatusSingleRecord($id, $status)
    {
        $db = Factory::getDBO();

        $query = 'UPDATE #__customtables_fields SET published=' . (int)$status . ' WHERE id=' . (int)$id;

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    public function delete()
    {
        $tableid = $this->input->get('tableid', 0, 'int');

        if ($tableid != 0) {
            $tableRow = ESTables::getTableRowByIDAssoc($tableid);
            if (!is_object($tableRow) and $tableRow == 0) {
                Factory::getApplication()->enqueueMessage('Table not found', 'error');
                return;
            } else {
                $tablename = $tableRow['tablename'];
            }
        } else {
            Factory::getApplication()->enqueueMessage('Table not set', 'error');
            return;
        }

        $paramsArray = [];
        $paramsArray['estableid'] = $tableid;
        $paramsArray['establename'] = $tablename;
        $_params = new JRegistry;
        $_params->loadArray($paramsArray);

        $ct = new CT($_params, false);
        $ct->setTable($tableRow);

        $cid = Factory::getApplication()->input->post->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);

        foreach ($cid as $id) {
            if ((int)$id != 0) {
                $id = (int)$id;
                $ok = Fields::deleteField_byID($ct, $id);
                if (!$ok)
                    break;
            }
        }

        $redirect = 'index.php?option=' . $this->option;
        $redirect .= '&view=listoffields&tableid=' . (int)$tableid;

        $msg = 'COM_CUSTOMTABLES_LISTOFFIELDS_N_ITEMS_DELETED';
        if (count($cid) == 1)
            $msg .= '_1';

        Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg, count($cid)), 'success');

        // Redirect to the item screen.
        $this->setRedirect(
            JRoute::_(
                $redirect, false
            )
        );
    }
}
