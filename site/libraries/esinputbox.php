<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\Inputbox;
use Joomla\CMS\Factory;

class ESInputBox
{
    var string $requiredLabel = '';
    var CustomTables\CT $ct;
    var $jinput;

    function __construct(CustomTables\CT &$ct)
    {
        $this->ct = &$ct;
        $this->jinput = Factory::getApplication()->input;
        $this->requiredLabel = 'COM_CUSTOMTABLES_REQUIREDLABEL';
    }

    function renderFieldBox(array $fieldrow, ?array $row, array $option_list)
    {
        $Inputbox = new Inputbox($this->ct, $fieldrow, $option_list, false);

        $realFieldName = $fieldrow['realfieldname'];

        if ($this->ct->Env->frmt == 'json') {
            //This is the field options for JSON output

            $shortFieldObject = Fields::shortFieldObject($fieldrow, ($row[$realFieldName] ?? null), $option_list);

            if ($fieldrow['type'] == 'sqljoin') {
                $typeparams = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams']);

                if (isset($option_list[2]) and $option_list[2] != '')
                    $typeparams[2] = $option_list[2];//Overwrites field type filter parameter.

                $typeparams[6] = 'json'; // to get the Object instead of the HTML element.

                $attributes_ = '';
                $value = '';
                $place_holder = '';
                $class = '';

                $list_of_values = JHTML::_('ESSQLJoin.render',
                    $typeparams,
                    $value,
                    false,
                    $this->ct->Languages->Postfix,
                    $this->ct->Env->field_input_prefix . $fieldrow['fieldname'],
                    $place_holder,
                    $class,
                    $attributes_);

                $shortFieldObject['value_options'] = $list_of_values;
            }

            return $shortFieldObject;
        }

        $value = $Inputbox->getDefaultValueIfNeeded($row);

        return $Inputbox->render($value, $row);
    }
}
