<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use CustomTablesImageMethods;
use Exception;
use Joomla\CMS\Component\ComponentHelper;

use CT_FieldTypeTag_image;
use CT_FieldTypeTag_file;
use CustomTables\DataTypes\Tree;

use LayoutProcessor;
use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;
use CustomTables\CustomPHP\CleanExecute;
use JoomlaBasicMisc;

class SaveFieldQuerySet
{
    var CT $ct;
    public Field $field;
    var ?array $row_old;
    var ?array $row_new;
    var bool $isCopy;

    //var array $saveQuery;

    function __construct(CT &$ct, $row, $isCopy = false)
    {
        $this->ct = &$ct;
        $this->row_old = $row;
        $this->row_new = [];

        $this->isCopy = $isCopy;
        //$this->saveQuery = [];
    }

    //Return type: null|string|array

    function getSaveFieldSet($fieldRow): void
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row_old);
        $this->getSaveFieldSetType();

        if ($this->field->defaultvalue != "" and !isset($this->row_old[$this->field->realfieldname]))//(is_null($query) or is_null($this->row[$this->field->realfieldname])))
            $this->applyDefaults($fieldRow);
    }

    protected function getSaveFieldSetType()
    {
        $listing_id = $this->row_old[$this->ct->Table->realidfieldname];
        switch ($this->field->type) {
            case 'records':
                $value = self::get_record_type_value($this->field);
                if ($value === null) {
                    //$this->row[$this->field->realfieldname] = null;
                    return;// null;
                } elseif ($value === '') {
                    $this->setNewValue(null);
                    //$this->row_new[$this->field->realfieldname] = null;
                    return;// $this->field->realfieldname . '=NULL';
                }
                $this->setNewValue($value);
                //$this->setNewValue($value);;
                return;// $this->field->realfieldname . '=' . database::quote($value);

            case 'sqljoin':
                $value = common::inputGetString($this->field->comesfieldname);

                if (isset($value)) {
                    $value = preg_replace("/[^A-Za-z\d\-]/", '', $value);

                    if ($value === null)
                        return;

                    if ($value == '') {
                        $this->setNewValue(null);
                        return;
                    }

                    if (is_numeric($value)) {
                        if ($value == 0) {
                            $this->setNewValue(null);
                            return;
                        }
                    }
                    $this->setNewValue($value);
                    return;
                }
                break;
            case 'radio':
                $value = common::inputGetCmd($this->field->comesfieldname);

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . database::quote($value);
                }
                break;

            case 'string':
            case 'filelink':
            case 'googlemapcoordinates':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . database::quote($value);
                }
                break;

            case 'color':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    if (str_contains($value, 'rgb')) {
                        $parts = str_replace('rgba(', '', $value);
                        $parts = str_replace('rgb(', '', $parts);
                        $parts = str_replace(')', '', $parts);
                        $values = explode(',', $parts);

                        if (count($values) >= 3) {
                            $r = $this->toHex((int)$values[0]);
                            $g = $this->toHex((int)$values[1]);
                            $b = $this->toHex((int)$values[2]);
                            $value = $r . $g . $b;
                        }

                        if (count($values) == 4) {
                            $a = 255 * (float)$values[3];
                            $value .= $this->toHex($a);
                        }

                    } else
                        $value = common::inputGetAlnum($this->field->comesfieldname, '');

                    $value = strtolower($value);
                    $value = str_replace('#', '', $value);
                    if (ctype_xdigit($value) or $value == '') {
                        $this->setNewValue($value);
                        return;// $this->field->realfieldname . '=' . database::quote($value);
                    }
                }
                break;

            case 'alias':
                $value = common::inputGetString($this->field->comesfieldname);

                if (isset($value)) {
                    $value = $this->get_alias_type_value($listing_id);
                    $this->setNewValue($value);
                    return;// ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));
                }
                break;

            case 'multilangstring':

                $firstLanguage = true;
                //$sets = [];
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = common::inputGetString($this->field->comesfieldname . $postfix);

                    if (isset($value)) {
                        $this->row_old[$this->field->realfieldname . $postfix] = $value;
                        $this->row_new[$this->field->realfieldname . $postfix] = $value;
                        //$sets[] = $this->field->realfieldname . $postfix . '=' . database::quote($value);
                    }
                }
                return;// (count($sets) > 0 ? $sets : null);

            case 'text':

                $value = ComponentHelper::filterText(common::inputPost($this->field->comesfieldname, null, 'raw'));

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . database::quote(stripslashes($value));
                }
                break;

            case 'multilangtext':

                //$sets = [];
                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = ComponentHelper::filterText(common::inputPost($this->field->comesfieldname . $postfix, null, 'raw'));

                    if (isset($value)) {
                        $this->row_old[$this->field->realfieldname . $postfix] = $value;
                        $this->row_new[$this->field->realfieldname . $postfix] = $value;
                        //$sets[] = $this->field->realfieldname . $postfix . '=' . database::quote($value);
                    }
                }
                return;// (count($sets) > 0 ? $sets : null);

            case 'ordering':
                $value = common::inputGetInt($this->field->comesfieldname);

                if (isset($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'int':
                $value = common::inputGetInt($this->field->comesfieldname);

                if (!is_null($value)) // always check with isset(). null doesn't work as 0 is null somehow in PHP
                {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'user':
                $value = common::inputPost($this->field->comesfieldname);

                if (isset($value)) {
                    $value = common::inputGetInt($this->field->comesfieldname);

                    if ($value == 0)
                        $value = null;

                    //if ($value === null)
                    $this->setNewValue($value);
                    //return $this->field->realfieldname . '=null';
                    //else
                    //$this->setNewValue($value);
                    //return $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'userid':

                if ($this->ct->isRecordNull($this->row_old) or $this->isCopy) {

                    $value = common::inputPost($this->field->comesfieldname);

                    if ((!isset($value) or $value == 0)) {

                        if ($value == 0)
                            $value = null;

                        if (!$this->ct->isRecordNull($this->row_old)) {
                            if ($this->row_old[$this->field->realfieldname] == null or $this->row_old[$this->field->realfieldname] == "")
                                $value = ($this->ct->Env->user->id != 0 ? $this->ct->Env->user->id : 0);
                        } else {
                            $value = ($this->ct->Env->user->id != 0 ? $this->ct->Env->user->id : 0);
                        }
                    }
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                }

                $value = common::inputGetInt($this->field->comesfieldname);
                if ($value == 0)
                    $value = null;

                if (isset($value) and $value != 0) {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'article':
            case 'usergroup':
                $value = common::inputGetInt($this->field->comesfieldname);

                if (isset($value)) {
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                }
                break;

            case 'usergroups':
                $value = $this->get_usergroups_type_value();
                $this->setNewValue($value);
                return;// ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));

            case 'language':
                $value = $this->get_customtables_type_language();
                $this->setNewValue($value);
                return;// ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));

            case 'float':
                $value = common::inputGetFloat($this->field->comesfieldname);

                if (isset($value)) {
//                    $this->row_new[$this->field->realfieldname] = (float)$value;
                    $this->setNewValue((float)$value);
                    return;// $this->field->realfieldname . '=' . (float)$value;
                }
                break;

            case 'image':

                $to_delete = common::inputPost($this->field->comesfieldname . '_delete', '', 'CMD');
                $returnValue = null;

                if ($to_delete == 'true') {
                    $this->setNewValue(null);
                    $returnValue = $this->field->realfieldname . '=NULL';

                    $ExistingImage = Tree::isRecordExist($listing_id, $this->ct->Table->realidfieldname, $this->field->realfieldname, $this->field->ct->Table->realtablename);

                    if ($ExistingImage !== null and ($ExistingImage != '' or (is_numeric($ExistingImage) and $ExistingImage > 0))) {

                        $imageMethods = new CustomTablesImageMethods;
                        $ImageFolder = CustomTablesImageMethods::getImageFolder($this->field->params);
                        $imageMethods->DeleteExistingSingleImage(
                            $ExistingImage,
                            JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder,
                            $this->field->params[0],
                            $this->field->ct->Table->realtablename,
                            $this->field->realfieldname,
                            $this->field->ct->Table->realidfieldname);
                    }
                }

                $tempValue = common::inputPostString($this->field->comesfieldname);
                if ($tempValue !== null and $tempValue != '') {

                    require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_image.php');

                    $value = CT_FieldTypeTag_image::get_image_type_value($this->field, $this->ct->Table->realidfieldname, $listing_id);
                    $this->setNewValue($value);

                    return;// ($value === null ? $this->field->realfieldname . '=NULL' : $this->field->realfieldname . '=' . database::quote($value));
                }

                //if ($returnValue !== null)
                //return $returnValue;

                break;

            case 'blob':

                $to_delete = common::inputPost($this->field->comesfieldname . '_delete', '', 'CMD');
                $value = CT_FieldTypeTag_file::get_blob_value($this->field);

                $fileNameField = '';
                if (isset($this->field->params[2])) {
                    $fileNameField_String = $this->field->params[2];
                    $fileNameField_Row = Fields::FieldRowByName($fileNameField_String, $this->ct->Table->fields);
                    $fileNameField = $fileNameField_Row['realfieldname'];
                }

                if ($to_delete == 'true' and $value === null) {

                    $this->setNewValue(null);

                    if ($fileNameField != '' and !$this->checkIfFieldAlreadyInTheList($fileNameField))
                        $this->row_new[$fileNameField] = null;

                    // $this->field->realfieldname . '=NULL';
                } else {
                    $this->setNewValue(strlen($value));

                    if ($fileNameField != '') {
                        $file_id = common::inputPost($this->field->comesfieldname, '', 'STRING');
                        $file_name_parts = explode('_', $file_id);
                        $file_name = implode('_', array_slice($file_name_parts, 3));
                        //$this->row_new[$fileNameField] = $file_name;

                        //$sets = array();
                        if ($value !== null and !$this->checkIfFieldAlreadyInTheList($fileNameField)) {
                            $this->row_new[$fileNameField] = $file_name;
                            //$sets[] = $fileNameField . '=' . database::quote($file_name);
                        }

                        //$sets[] = ($value === null ? null : $this->field->realfieldname . '=FROM_BASE64("' . base64_encode($value) . '")');
                        // $sets;
                    } else {
                        $this->row_new[$fileNameField] = $value;
                        // ($value === null ? null : $this->field->realfieldname . '=FROM_BASE64("' . base64_encode($value) . '")');
                    }
                }
                return;

            case 'file':

                $file_type_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
                require_once($file_type_file);

                $value = CT_FieldTypeTag_file::get_file_type_value($this->field, $listing_id);

                $to_delete = common::inputPost($this->field->comesfieldname . '_delete', '', 'CMD');

                if ($to_delete == 'true' and $value === null) {
                    $this->setNewValue(null);
                    // $this->field->realfieldname . '=NULL';
                } else {
                    $this->setNewValue($value);
                    // ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));
                }
                return;

            case 'signature':

                $value = $this->get_customtables_type_signature();
                $this->setNewValue($value);
                return;// ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));

            case 'multilangarticle':

                //$sets = [];
                $firstLanguage = true;
                foreach ($this->ct->Languages->LanguageList as $lang) {
                    if ($firstLanguage) {
                        $postfix = '';
                        $firstLanguage = false;
                    } else
                        $postfix = '_' . $lang->sef;

                    $value = common::inputGetInt($this->field->comesfieldname . $postfix);

                    if (isset($value)) {
                        $this->row_old[$this->field->realfieldname . $postfix] = $value;
                        $this->row_new[$this->field->realfieldname . $postfix] = $value;
                        //$sets[] = $this->field->realfieldname . $postfix . '=' . $value;
                    }
                }

                return;// (count($sets) > 0 ? $sets : null);

            case 'customtables':

                $value = $this->get_customtables_type_value();
                $this->setNewValue($value);
                return;// ($value === null ? null : $this->field->realfieldname . '=' . database::quote($value));

            case 'email':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    $value = trim($value ?? '');
                    if (Email::checkEmail($value)) {
                        $this->setNewValue($value);
                        // $this->field->realfieldname . '=' . database::quote($value);
                    } else {
                        $this->setNewValue(null);
                        // $this->field->realfieldname . '=' . database::quote("");//PostgreSQL compatible
                    }
                    return;
                }
                break;

            case 'url':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    $value = trim($value ?? '');

                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->setNewValue($value);
                        // $this->field->realfieldname . '=' . database::quote($value);
                    } else {
                        $this->setNewValue(null);
                        // $this->field->realfieldname . '=' . database::quote("");//PostgreSQL compatible
                    }
                    return;
                }
                break;

            case 'checkbox':
                $value = common::inputGetCmd($this->field->comesfieldname);

                if ($value !== null) {
                    if ((int)$value == 1 or $value == 'on')
                        $value = 1;
                    else
                        $value = 0;

                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . $value;
                } else {
                    $value = common::inputGetCmd($this->field->comesfieldname . '_off');
                    if ($value !== null) {
                        if ((int)$value == 1) {
                            $this->setNewValue(0);
                            //$this->row_new[$this->field->realfieldname] = 0;
                            // $this->field->realfieldname . '=0';
                        } else {
                            $this->setNewValue(1);
                            //$this->row_new[$this->field->realfieldname] = 1;
                            // $this->field->realfieldname . '=1';
                        }
                        return;
                    }
                }
                break;

            case 'date':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    if ($value == '' or $value == '0000-00-00') {

                        if (Fields::isFieldNullable($this->ct->Table->realtablename, $this->field->realfieldname)) {
                            $this->setNewValue(null);
                            // $this->field->realfieldname . '=NULL';
                        } else {
                            $this->setNewValue('0000-00-00 00:00:00');
                            //$this->row_new[$this->field->realfieldname] = '0000-00-00 00:00:00';
                            // $this->field->realfieldname . '=' . database::quote('0000-00-00 00:00:00');
                        }
                    } else {
                        $this->setNewValue($value);
                        // $this->field->realfieldname . '=' . database::quote($value);
                    }
                    return;
                }
                break;

            case 'time':
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value)) {
                    if ($value == '') {
                        $this->setNewValue(null);
                        // $this->field->realfieldname . '=NULL';
                    } else {
                        $this->setNewValue((int)$value);
                        //$this->row_new[$this->field->realfieldname] = (int)$value;
                        // $this->field->realfieldname . '=' . (int)$value;
                    }
                    return;
                }
                break;

            case 'creationtime':
                if ($this->row_old[$this->ct->Table->realidfieldname] == 0 or $this->row_old[$this->ct->Table->realidfieldname] == '' or $this->isCopy) {
                    $value = gmdate('Y-m-d H:i:s');
                    $this->setNewValue($value);

                    return;// $this->field->realfieldname . '=' . database::quote($value);
                }
                break;

            case 'changetime':
                $value = gmdate('Y-m-d H:i:s');
                $this->setNewValue($value);
                return;// $this->field->realfieldname . '=' . database::quote($value);

            case 'server':

                if (count($this->field->params) == 0)
                    $value = self::getUserIP(); //Try to get client real IP
                else
                    $value = common::inputServer($this->field->params[0], '', 'STRING');

                $this->setNewValue($value);
                return;// $this->field->realfieldname . '=' . database::quote($value);

            case 'id':
                //get max id
                if ($this->row_old[$this->ct->Table->realidfieldname] == 0 or $this->row_old[$this->ct->Table->realidfieldname] == '' or $this->isCopy) {
                    $minid = (int)$this->field->params[0];

                    $query = 'SELECT MAX(' . $this->ct->Table->realidfieldname . ') AS maxid FROM ' . $this->ct->Table->realtablename . ' LIMIT 1';
                    $rows = database::loadObjectList($query);
                    if (count($rows) != 0) {
                        $value = (int)($rows[0]->maxid) + 1;
                        if ($value < $minid)
                            $value = $minid;

                        $this->setNewValue($value);
                        return;// $this->field->realfieldname . '=' . database::quote($value);
                    }
                }
                break;

            case 'md5':

                $vlu = '';
                $fields = explode(',', $this->field->params[0]);
                foreach ($fields as $f1) {
                    if ($f1 != $this->field->fieldname) {
                        //to make sure that field exists
                        foreach ($this->ct->Table->fields as $f2) {
                            if ($f2['fieldname'] == $f1)
                                $vlu .= $this->row_old[$f2['realfieldname']];
                        }
                    }
                }

                if ($vlu != '') {
                    $value = md5($vlu);
                    $this->setNewValue($value);
                    return;// $this->field->realfieldname . '=' . database::quote($value);
                }
                break;
        }
        return null;
    }

    public static function get_record_type_value(Field $field): ?string
    {
        if (count($field->params) > 2) {
            $esr_selector = $field->params[2];
            $selectorPair = explode(':', $esr_selector);

            switch ($selectorPair[0]) {
                case 'single';
                    $value = common::inputGetInt($field->comesfieldname);

                    if (isset($value))
                        return $value;

                    break;

                case 'radio':
                case 'checkbox':
                case 'multi':

                    //returns NULL if field parameter not found - nothing to save
                    //returns empty array if nothing selected - save empty value
                    $valueArray = common::inputPost($field->comesfieldname, null, 'array');

                    if ($valueArray) {
                        return self::getCleanRecordValue($valueArray);
                    } else {
                        $value_off = common::inputPostInt($field->comesfieldname . '_off');
                        if ($value_off) {
                            return '';
                        } else {
                            return null;
                        }
                    }

                case 'multibox';
                    $valueArray = common::inputPost($field->comesfieldname, null, 'array');

                    if (isset($valueArray)) {
                        return self::getCleanRecordValue($valueArray);
                    }
                    break;
            }
        }
        return null;
    }

    protected static function getCleanRecordValue($array): string
    {
        $values = array();
        foreach ($array as $a) {
            if ((int)$a != 0)
                $values[] = (int)$a;
        }
        return ',' . implode(',', $values) . ',';
    }

    function setNewValue($value): void
    {
        //Original value but modified during the process
        $this->row_old[$this->field->realfieldname] = $value;
        //$this->row_new is empty at the beginning and if record needs to be updated new item with the key is added.
        $this->row_new[$this->field->realfieldname] = $value;
    }

    protected function toHex($n): string
    {
        $n = intval($n);
        if (!$n)
            return '00';

        $n = max(0, min($n, 255)); // make sure the $n is not bigger than 255 and not less than 0
        $index1 = (int)($n - ($n % 16)) / 16;
        $index2 = (int)$n % 16;

        return substr("0123456789ABCDEF", $index1, 1)
            . substr("0123456789ABCDEF", $index2, 1);
    }

    public function get_alias_type_value($listing_id)
    {
        $value = common::inputGetString($this->field->comesfieldname);
        if (!isset($value))
            return null;

        $value = $this->prepare_alias_type_value($listing_id, $value);
        if ($value == '')
            return null;

        return $value;
    }

    public function prepare_alias_type_value($listing_id, $value)
    {
        $value = JoomlaBasicMisc::slugify($value);

        if ($value == '')
            return '';

        if (!$this->checkIfAliasExists($listing_id, $value, $this->field->realfieldname))
            return $value;

        $val = $this->splitStringToStringAndNumber($value);

        $value_new = $val[0];
        $i = $val[1];

        while (1) {
            if ($this->checkIfAliasExists($listing_id, $value_new, $this->field->realfieldname)) {
                //increase index
                $i++;
                $value_new = $val[0] . '-' . $i;
            } else
                break;
        }
        return $value_new;
    }

    protected function checkIfAliasExists($exclude_id, $value, $realfieldname): bool
    {
        $query = 'SELECT count(' . $this->ct->Table->realidfieldname . ') AS c FROM ' . $this->ct->Table->realtablename . ' WHERE '
            . $this->ct->Table->realidfieldname . '!=' . (int)$exclude_id . ' AND ' . $realfieldname . '=' . database::quote($value) . ' LIMIT 1';

        $rows = database::loadObjectList($query);
        if (count($rows) == 0)
            return false;

        $c = (int)$rows[0]->c;

        if ($c > 0)
            return true;

        return false;
    }

    protected function splitStringToStringAndNumber($string): array
    {
        if ($string == '')
            return array('', 0);

        $pair = explode('-', $string);
        $l = count($pair);

        if ($l == 1)
            return array($string, 0);

        $c = end($pair);
        if (is_numeric($c)) {
            unset($pair[$l - 1]);
            $pair = array_values($pair);
            $val = array(implode('-', $pair), intval($c));
        } else
            return array($string, 0);

        return $val;
    }

    protected function get_usergroups_type_value(): ?string
    {
        switch ($this->field->params[0]) {
            case 'radio':
            case 'single';
                $value = common::inputGetString($this->field->comesfieldname);
                if (isset($value))
                    return ',' . $value . ',';

                break;
            case 'multibox':
            case 'checkbox':
            case 'multi';
                $valueArray = common::inputPost($this->field->comesfieldname, null, 'array');
                if (isset($valueArray))
                    return ',' . implode(',', $valueArray) . ',';

                break;
        }
        return null;
    }

    protected function get_customtables_type_language(): ?string
    {
        $value = common::inputGetCmd($this->field->comesfieldname);

        if (isset($value))
            return $value;

        return null;
    }

    function checkIfFieldAlreadyInTheList(string $realFieldName): bool
    {
        return isset($this->row_new[$realFieldName]);
        /*
    foreach ($this->saveQuery as $query) {
        $parts = explode('=', $query);

        if ($parts[0] == $fieldName)
            return true;
    }
    return false;
        */
    }

    protected function get_customtables_type_signature(): ?string
    {
        $value = common::inputGetString($this->field->comesfieldname);

        if (isset($value)) {
            $ImageFolder = CustomTablesImageMethods::getImageFolder($this->field->params);

            $format = $this->field->params[3] ?? 'png';

            if ($format == 'svg-db') {
                return $value;
            } else {
                if ($format == 'jpeg')
                    $format = 'jpg';

                //Get new file name and avoid possible duplicate

                $i = 0;
                do {
                    $ImageID = date("YmdHis") . ($i > 0 ? $i : '');
                    //there is possible error, check all possible ext
                    $image_file = JPATH_SITE . DIRECTORY_SEPARATOR . $ImageFolder . DIRECTORY_SEPARATOR . $ImageID . '.' . $format;
                    $i++;
                } while (file_exists($image_file));

                $parts = explode(';base64,', $value);

                $decoded_binary = base64_decode($parts[1]);
                file_put_contents($image_file, $decoded_binary);

                return $ImageID;
            }
        }
        return null;
    }

    protected function get_customtables_type_value(): ?string
    {
        $optionname = $this->field->params[0];

        if ($this->field->params[1] == 'multi') {
            $value = $this->getMultiString($optionname);

            if ($value !== null) {
                if ($value != '')
                    return ',' . $value . ',';
                else
                    return '';
            }
        } elseif ($this->field->params[1] == 'single') {
            $value = $this->getComboString($optionname);

            if ($value !== null) {
                if ($value != '')
                    return ',' . $value . ',';
                else
                    return '';
            }
        }
        return null;
    }

    protected function getMultiString($parent): ?string
    {
        $prefix = $this->field->prefix . 'multi_' . $this->ct->Table->tablename . '_' . $this->field->fieldname;

        $parentId = Tree::getOptionIdFull($parent);
        $a = $this->getMultiSelector($parentId, $parent, $prefix);
        if ($a === null)
            return null;

        if (count($a) == 0)
            return '';
        else
            return implode(',', $a);

    }

    protected function getMultiSelector($parentId, $parentName, $prefix): ?array
    {
        $set = false;
        $resultList = array();

        $rows = $this->getList($parentId);
        if (count($rows) < 1)
            return $resultList;

        foreach ($rows as $row) {
            if (strlen($parentName) == 0)
                $ChildList = $this->getMultiSelector($row->id, $row->optionname, $prefix);
            else
                $ChildList = $this->getMultiSelector($row->id, $parentName . '.' . $row->optionname, $prefix);

            if ($ChildList !== null)
                $count_child = count($ChildList);
            else
                $count_child = 0;

            if ($count_child > 0) {
                $resultList = array_merge($resultList, $ChildList);
            } else {
                $value = common::inputGetString($prefix . '_' . $row->id);
                if (isset($value)) {
                    $set = true;

                    if (strlen($parentName) == 0)
                        $resultList[] = $row->optionname . '.';
                    else
                        $resultList[] = $parentName . '.' . $row->optionname . '.';
                }
            }
        }

        if (!$set)
            return null;

        return $resultList;
    }

    protected function getList($parentId)
    {
        $query = 'SELECT id, optionname FROM #__customtables_options WHERE parentid=' . (int)$parentId;
        return database::loadObjectList($query);
    }

    protected function getComboString($parent): ?string
    {
        $prefix = $this->field->prefix . 'combotree_' . $this->ct->Table->tablename . '_' . $this->field->fieldname;
        $i = 1;
        $result = array();
        $v = '';
        $set = false;
        do {
            $value = common::inputGetCmd($prefix . '_' . $i);
            if (isset($value)) {
                if ($value != '') {
                    $result[] = $value;
                    $i++;
                }
                $set = true;
            } else
                break;

        } while ($v != '');

        if (count($result) == 0) {
            if ($set)
                return '';
            else
                return null;
        } else
            return $parent . '.' . implode('.', $result) . '.';

        // the format of the string is: ",[optionname1].[optionname2].[optionname..n].,
        // example: ,geo.usa.newyork.,
        // last "." dot is to let search by parents
        // php example: getpos(",geo.usa.",$string)
        // mysql example: instr($string, ",geo.usa.")
    }

    public static function getUserIP(): string
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                if ($_SERVER['HTTP_X_FORWARDED_FOR'] == '')
                    return '';

                $address = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);

                return trim($address[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    function applyDefaults($fieldRow): void
    {
        $this->field = new Field($this->ct, $fieldRow, $this->row_old);
        //if (!Fields::isVirtualField($fieldRow) and $this->field->defaultvalue != "" and (!isset($this->row[$this->field->realfieldname]) or is_null($this->row[$this->field->realfieldname])) and $this->field->type != 'dummie') {
        if (!Fields::isVirtualField($fieldRow) and $this->field->defaultvalue != "" and !isset($this->row_old[$this->field->realfieldname]) and $this->field->type != 'dummie') {

            if ($this->ct->Env->legacySupport) {
                $LayoutProc = new LayoutProcessor($this->ct);
                $LayoutProc->layout = $this->field->defaultvalue;
                $this->field->defaultvalue = $LayoutProc->fillLayout($this->row_old);
            }

            $twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
            $value = $twig->process($this->row_old);

            if ($twig->errorMessage !== null) {
                $this->ct->errors[] = $twig->errorMessage;
                return;
            }

            if ($value == '') {
                $this->setNewValue(null);
                // $this->field->realfieldname . '=NULL';
            } else {
                $this->setNewValue($value);
                // $this->field->realfieldname . '=' . database::quote($value);
            }
            //return;

        } elseif ($fieldRow['type'] == 'virtual') {

            $storage = $this->field->params[1] ?? '';

            if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned" or $storage == "storedstring") {

                try {
                    $code = str_replace('****quote****', '"', $this->field->params[0]);
                    $code = str_replace('****apos****', "'", $code);
                    $twig = new TwigProcessor($this->ct, $code, false, false, true);
                    $value = @$twig->process($this->row_old);

                    if ($twig->errorMessage !== null) {
                        echo $twig->errorMessage;
                        $this->ct->errors[] = $twig->errorMessage;
                        return;
                    }

                } catch (Exception $e) {
                    echo $e->getMessage();
                    $this->ct->errors[] = $e->getMessage();
                    return;
                }

                if ($storage == "storedintegersigned" or $storage == "storedintegerunsigned") {
                    $this->setNewValue((int)$value);
                    return;
                }

                $this->setNewValue($value);
                //return;// $this->field->realfieldname . '=' . database::quote($value);
            }
        }
        //return;// null;
    }

    public function Try2CreateUserAccount($field): bool
    {
        $uid = (int)$this->ct->Table->record[$field->realfieldname];

        if ($uid != 0) {

            $email = $this->ct->Env->user->email . '';
            if ($email != '') {
                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS');
                return false; //all good, user already assigned.
            }
        }

        if (count($field->params) < 3) {
            $this->ct->errors[] = common::translate('User field name parameters count is less than 3.');
            return false;
        }

        //Try to create user
        $new_parts = array();

        foreach ($field->params as $part) {

            if ($this->ct->Env->legacySupport) {
                tagProcessor_General::process($this->ct, $part, $this->ct->Table->record);
                tagProcessor_Item::process($this->ct, $part, $this->ct->Table->record, '');
                tagProcessor_If::process($this->ct, $part, $this->ct->Table->record);
                tagProcessor_Page::process($this->ct, $part);
                tagProcessor_Value::processValues($this->ct, $part, $this->ct->Table->record);
            }

            $twig = new TwigProcessor($this->ct, $part, false, false, false);
            $part = $twig->process($this->ct->Table->record);

            if ($twig->errorMessage !== null) {
                $this->ct->errors[] = $twig->errorMessage;
                return false;
            }

            $new_parts[] = $part;
        }

        $user_groups = $new_parts[0];
        $user_name = $new_parts[1];
        $user_email = $new_parts[2];

        if ($user_groups == '') {
            $this->ct->errors[] = common::translate('User group field not set.');
            return false;
        } elseif ($user_name == '') {
            $this->ct->errors[] = common::translate('User name field not set.');
            return false;
        } elseif ($user_email == '') {
            $this->ct->errors[] = common::translate('User email field not set.');
            return false;
        }

        $unique_users = false;
        if (isset($new_parts[4]) and $new_parts[4] == 'unique')
            $unique_users = true;

        $existing_user_id = CTUser::CheckIfEmailExist($user_email, $existing_user, $existing_name);

        if ($existing_user_id) {
            if (!$unique_users) //allow not unique record per users
            {
                CTUser::UpdateUserField($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $field->realfieldname,
                    $existing_user_id, $this->ct->Table->record[$this->ct->Table->realidfieldname]);

                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_RECORD_USER_UPDATED');
            } else {
                $this->ct->errors[] =
                    common::translate('COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL')
                    . ' "' . $user_email . '" '
                    . common::translate('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS');
            }
        } else {
            CTUser::CreateUser($this->ct->Table->realtablename, $this->ct->Table->realidfieldname, $user_email, $user_name,
                $user_groups, $this->ct->Table->record[$this->ct->Table->realidfieldname], $field->realfieldname);
        }
        return true;
    }

    public function checkSendEmailConditions(string $listing_id, string $condition): bool
    {
        if ($condition == '')
            return true; //if no conditions

        $this->ct->Table->loadRecord($listing_id);
        $Layouts = new Layouts($this->ct);
        $parsed_condition = $Layouts->parseRawLayoutContent($condition);
        $parsed_condition = '(' . $parsed_condition . ' ? 1 : 0)';

        $error = '';
        if ($this->ct->Env->advancedTagProcessor)
            $value = CleanExecute::execute($parsed_condition, $error);
        else
            $value = $parsed_condition;

        if ($error != '') {
            $this->ct->errors[] = $error;
            return false;
        }

        if ((int)$value == 1)
            return true;

        return false;
    }

    function sendEmailIfAddressSet(string $listing_id, array $row): void//,$new_username,$new_password)
    {
        if ($this->ct->Params->onRecordAddSendEmailTo != '')
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordAddSendEmailTo, $row);
        else
            $status = $this->sendEmailNote($listing_id, $this->ct->Params->onRecordSaveSendEmailTo, $row);

        if ($this->ct->Params->emailSentStatusField != '') {

            foreach ($this->ct->Table->fields as $fieldrow) {
                $fieldname = $fieldrow['fieldname'];
                if ($this->ct->Params->emailSentStatusField == $fieldname) {

                    $query = 'UPDATE ' . $this->ct->Table->realtablename . ' SET es_' . $fieldname . '=' . $status . ' WHERE ' . $this->ct->Table->realidfieldname . '=' . database::quote($listing_id);
                    database::setQuery($query);
                    return;
                }
            }
        }
    }

    function sendEmailNote(string $listing_id, string $emails, array $row): int
    {
        $this->ct->Table->loadRecord($listing_id);

        //Prepare Email List
        $emails_raw = JoomlaBasicMisc::csv_explode(',', $emails, '"', true);

        $emails = array();
        foreach ($emails_raw as $SendToEmail) {
            $EmailPair = JoomlaBasicMisc::csv_explode(':', trim($SendToEmail));
            $Layouts = new Layouts($this->ct);
            $EmailTo = $Layouts->parseRawLayoutContent(trim($EmailPair[0]), false);

            if (isset($EmailPair[1]) and $EmailPair[1] != '')
                $Subject = $Layouts->parseRawLayoutContent($EmailPair[1]);
            else
                $Subject = 'Record added to "' . $this->ct->Table->tabletitle . '"';

            if ($EmailTo != '')
                $emails[] = array('email' => $EmailTo, 'subject' => $Subject);
        }

        $Layouts = new Layouts($this->ct);
        $message_layout_content = $Layouts->getLayout($this->ct->Params->onRecordAddSendEmailLayout);
        $note = $Layouts->parseRawLayoutContent($message_layout_content);
        $status = 0;

        foreach ($emails as $SendToEmail) {
            $EmailTo = $SendToEmail['email'];
            $Subject = $SendToEmail['subject'];

            $attachments = [];

            $options = array();
            $fList = JoomlaBasicMisc::getListToReplace('attachment', $options, $note, '{}');
            $i = 0;
            $note_final = $note;
            foreach ($fList as $fItem) {
                $filename = $options[$i];
                if (file_exists($filename)) {
                    $attachments[] = $filename;//TODO: Check the functionality
                    $vlu = '';
                } else
                    $vlu = '<p>File "' . $filename . '"not found.</p>';

                $note_final = str_replace($fItem, $vlu, $note);
                $i++;
            }

            foreach ($this->ct->Table->fields as $fieldrow) {
                if ($fieldrow['type'] == 'file') {
                    $field = new Field($this->ct, $fieldrow, $row);
                    $FileFolder = CT_FieldTypeTag_file::getFileFolder($field->params[0]);

                    $filename = $FileFolder . $this->ct->Table->record[$fieldrow['realfieldname']];
                    if (file_exists($filename))
                        $attachments[] = $filename;//TODO: Check the functionality
                }
            }

            $sent = Email::sendEmail($EmailTo, $Subject, $note_final, true, $attachments);

            if ($sent !== true) {
                //Something went wrong. Email not sent.
                $this->ct->errors[] = common::translate('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL') . ': ' . $EmailTo . ' (' . $Subject . ')';
                $status = 0;
            } else {
                $this->ct->messages[] = common::translate('COM_CUSTOMTABLES_EMAIL_SENT_TO') . ': ' . $EmailTo . ' (' . $Subject . ')';
                $status = 1;
            }
        }
        return $status;
    }

}
