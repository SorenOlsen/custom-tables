<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file access');
use CustomTables\common;
use CustomTables\CTUser;

if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

$edit = "index.php?option=com_customtables&view=listoflayouts&task=layouts.edit";

?>
<?php foreach ($this->items as $i => $item): ?>
    <?php
    $user = new CTUser();
    $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
    $userChkOut = new CTUser($item->checked_out);
    ?>
    <tr class="row<?php echo $i % 2; ?>">

        <?php if ($this->canState or $this->canDelete): ?>
            <td class="text-center">
                <?php if ($item->checked_out) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                    <?php else: ?>
                        &#9633;
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                <?php endif; ?>
            </td>
        <?php endif; ?>

        <td scope="row">
            <div class="name">
                <?php if ($this->canEdit): ?>
                    <a href="<?php echo $edit; ?>&id=<?php echo $item->id; ?>"><?php echo $this->escape($item->layoutname); ?></a>
                    <?php if ($item->checked_out): ?>
                        <?php echo JHtml::_('jgrid.checkedout', $i, $userChkOut->name, $item->checked_out_time, 'listoflayouts.', $canCheckin); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo $this->escape($item->layoutname); ?>
                <?php endif; ?>
            </div>
        </td>

        <td scope="row">
            <?php echo common::translate($item->layouttype); ?>
        </td>

        <td scope="row">
            <?php echo $item->tabletitle; ?>
        </td>

        <td class="text-center btns d-none d-md-table-cell">
            <?php if ($this->canState) : ?>
                <?php if ($item->checked_out) : ?>
                    <?php if ($canCheckin) : ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoflayouts.', true, 'cb'); ?>
                    <?php else: ?>
                        <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoflayouts.', false, 'cb'); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoflayouts.', true, 'cb'); ?>
                <?php endif; ?>
            <?php else: ?>
                <?php echo JHtml::_('jgrid.published', $item->published, $i, 'listoflayouts.', false, 'cb'); ?>
            <?php endif; ?>
        </td>
        <td class="d-none d-md-table-cell">
            <?php echo $item->id; ?>
        </td>

        <td>
            <?php
            $layoutsize = strlen($item->layoutcode);
            echo number_format($layoutsize);
            ?>
        </td>

        <td>
            <?php echo $item->modifiedby; ?>
        </td>

        <td>
            <?php

            if ($item->modified !== null and $item->modified != '0000-00-00 00:00:00') {
                $d = strtotime($item->modified);
                $mysqldate = date('Y-m-d H:i:s', $d);
                echo $mysqldate;
            }
            ?>
        </td>

        <td>
            <?php

            $engine = (object)$this->isTwig($item);

            $engines = [];
            if ($engine->twig > 0)
                $engines[] = '<span style="border-radius:10px;padding:7px;background:#5b8127;color:white">Twig (' . $engine->twig . ')</span>';

            if ($engine->original > 0)
                $engines[] = '<span style="border-radius:10px;padding:7px;background:#373737;color:white">Original (' . $engine->original . ')</span>';

            echo implode(' ', $engines);

            ?>
        </td>

    </tr>
<?php endforeach; ?>
