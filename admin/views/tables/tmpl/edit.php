<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage edit.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\common;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

if ($this->version >= 4) {
	$wa = $this->document->getWebAssetManager();
	$wa->useScript('keepalive')->useScript('form.validate');
} else {
	HTMLHelper::_('behavior.formvalidation');
	HTMLHelper::_('behavior.keepalive');
}

$document = Factory::getDocument();
$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" rel="stylesheet">');

?>

<form action="<?php echo Route::_('index.php?option=com_customtables&layout=edit&id=' . (int)$this->item->id . $this->referral); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
    <div id="jform_title"></div>
    <div class="form-horizontal">

		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'tablesTab', array('active' => 'details')); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'tablesTab', 'details', common::translate('COM_CUSTOMTABLES_TABLES_DETAILS')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">

                <div class="control-group">
                    <div class="control-label"><?php echo $this->form->getLabel('tablename'); ?></div>
                    <div class="controls"><?php echo $this->form->getInput('tablename'); ?></div>
                </div>

                <hr/>

				<?php

				$moreThanOneLanguage = false;
				foreach ($this->ct->Languages->LanguageList as $lang) {
					$id = 'tabletitle';
					if ($moreThanOneLanguage) {
						$id .= '_' . $lang->sef;

						$cssclass = 'text_area';
						$att = '';
					} else {
						$cssclass = 'text_area required';
						$att = ' required aria-required="true"';
					}

					$item_array = (array)$this->item;
					$vlu = '';

					if (isset($item_array[$id]))
						$vlu = $item_array[$id];

					echo '
					<div class="control-group">
						<div class="control-label">' . $this->form->getLabel('tabletitle') . '</div>
						<div class="controls">
							<input type="text" name="jform[' . $id . ']" id="jform_' . $id . '"  value="' . $vlu . '" class="' . $cssclass . '"     placeholder="Table Title"   maxlength="255" ' . $att . ' />
							<b>' . $lang->title . '</b>
						</div>

					</div>
					';

					$moreThanOneLanguage = true; //More than one language installed
				}
				?>
                <hr/>
                <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                    <div class="control-label"><?php echo $this->form->getLabel('tablecategory'); ?></div>
                    <div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('tablecategory');
						?></div>
                </div>
            </div>
        </div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>


		<?php
		$moreThanOneLanguage = false;
		foreach ($this->ct->Languages->LanguageList as $lang) {
			$id = 'description';
			if ($moreThanOneLanguage)
				$id .= '_' . $lang->sef;

			echo HTMLHelper::_('bootstrap.addTab', 'tablesTab', $id, common::translate('COM_CUSTOMTABLES_TABLES_DESCRIPTION') . ' <b>' . $lang->title . '</b>');
			echo '
			<div id="' . $id . '" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">';


			$editor = Factory::getEditor();

			$item_array = (array)$this->item;
			$vlu = '';

			if (isset($item_array[$id]))
				$vlu = $item_array[$id];

			echo $editor->display('jform[' . $id . ']', $vlu, '100%', '300', '60', '5');

			echo '
					</div>
				</div>
			</div>';
			$moreThanOneLanguage = true; //More than one language installed

			echo HTMLHelper::_('bootstrap.endTab');
		}

		?>

		<?php
		//if($this->ct->Env->advancedTagProcessor):

		echo HTMLHelper::_('bootstrap.addTab', 'tablesTab', 'advanced', common::translate('COM_CUSTOMTABLES_TABLES_ADVANCED')); ?>

        <div class="row-fluid form-horizontal-desktop">
            <div class="span12">

                <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                    <div class="control-label"><?php echo $this->form->getLabel('customphp'); ?></div>
                    <div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('customphp');
						?></div>
                </div>

                <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                    <div class="control-label"><?php echo $this->form->getLabel('allowimportcontent'); ?></div>
                    <div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('allowimportcontent');
						?>
                    </div>
                </div>

                <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                    <div class="control-label"><?php echo $this->form->getLabel('customtablename'); ?></div>
                    <div class="controls"><?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('customtablename');
						?>
                    </div>
                </div>

                <div class="control-group<?php echo(!$this->ct->Env->advancedTagProcessor ? ' ct_pro' : ''); ?>">
                    <div class="control-label"><?php echo $this->form->getLabel('customidfield'); ?></div>
                    <div class="controls">
						<?php
						if (!$this->ct->Env->advancedTagProcessor)
							echo '<input type="text" value="Available in Pro Version" disabled="disabled" class="form-control valid form-control-success" />';
						else
							echo $this->form->getInput('customidfield');
						?>
                    </div>
                </div>
            </div>
        </div>

		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php

		if ($this->item->tablename !== null) {

			echo HTMLHelper::_('bootstrap.addTab', 'tablesTab', 'dependencies', common::translate('COM_CUSTOMTABLES_TABLES_DEPENDENCIES'));
			include('_dependencies.php');
			?>

            <div class="row-fluid form-horizontal-desktop">
                <div class="span12">

					<?php
					echo renderDependencies($this->item->id, $this->item->tablename);
					?>

                </div>
            </div>
			<?php echo HTMLHelper::_('bootstrap.endTab');
		}
		?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

        <div>
            <input type="hidden" name="task" value="tables.edit"/>
            <input type="hidden" name="originaltableid" value="<?php echo $this->item->id; ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>

    <div class="clearfix"></div>
	<?php echo JLayoutHelper::render('tables.details_under', $this); ?>

	<?php if (!$this->ct->Env->advancedTagProcessor): ?>
        <script>
            disableProField("jform_customtablename");
            disableProField("jform_customidfield");
        </script>
	<?php endif; ?>

</form>
