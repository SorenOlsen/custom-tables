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

use CustomTables\common;
use CustomTables\CTUser;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

jimport('joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditPhotos extends HtmlView
{
	function display($tpl = null)
	{
		$user = new CTUser;
		if ($user->id === null) {
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			return;
		}

		$this->Model = $this->getModel();
		$this->Model->load();
		$this->images = $this->Model->getPhotoList();

		$this->idList = array();

		foreach ($this->images as $image)
			$this->idList[] = $image->photoid;

		$this->max_file_size = JoomlaBasicMisc::file_upload_max_size();
		$this->Listing_Title = $this->Model->Listing_Title;
		$this->listing_id = $this->Model->listing_id;
		$this->galleryname = $this->Model->galleryname;

		parent::display($tpl);
	}

	function drawPhotos()
	{
		if (count($this->images) == 0)
			return '';

		$htmlOut = '

		<h2>' . common::translate('COM_CUSTOMTABLES_LIST_OF_FOTOS') . '</h2>
		<table>
			<thead>
				<tr>
					<td><input type="checkbox" name="SelectAllBox" id="SelectAllBox" onClick=SelectAll(this.checked) style="text-align:left;vertical-align:top"></td>
					<td></td>
					<td></td>
				</tr>
				<tr><td colspan="3"><hr></td></tr>
			</thead>
			<tbody>
		';

		$c = 0;
		foreach ($this->images as $image) {
			$htmlOut .= '
				<tr>';

			$imageFile = $this->Model->imagefolderweb . '/' . $this->Model->imagemainprefix . $this->Model->ct->Table->tableid . '_'
				. $this->Model->galleryname . '__esthumb_' . $image->photoid . '.jpg';

			$imageFileOriginal = $this->Model->imagefolderweb . '/' . $this->Model->imagemainprefix
				. $this->Model->ct->Table->tableid . '_' . $this->Model->galleryname . '__original_' . $image->photoid . '.' . $image->photo_ext;

			$htmlOut .= '
					<td style="text-align:center;vertical-align: top;">
						<input type="checkbox" name="esphoto' . $image->photoid . '" id="esphoto' . $image->photoid . '" style="text-align:left;vertical-align:top">
					</td>

					<td' . ($c == 0 ? ' class="MainImage" ' : '') . ' style="width:170px;text-align:center;">
						<a href="' . $imageFileOriginal . '" rel="shadowbox"><img src="' . $imageFile . '" alt="' . $image->title . '" title="' . $image->title . '" style="border:none;width:150px;height:150px;" /></a>
					</td>

					<td style="text-align:left;vertical-align: top;">
						<table border="0" cellpadding="5" style="margin-left:5px;">
							<tbody>
								<tr>
									<td>' . common::translate("COM_CUSTOMTABLES_TITLE") . ': </td>
									<td><input type="text"  style="width: 150px;" name="esphototitle' . $image->photoid . '" id="esphototitle' . $image->photoid . '" value="' . $image->title . '"></td>
								</tr>
								<tr>
									<td>' . common::translate("COM_CUSTOMTABLES_ORDER") . ': </td>
									<td><input type="text"  style="width: 100px;" name="esphotoorder' . $image->photoid . '" id="esphotoorder' . $image->photoid . '" value="' . $image->ordering . '"></td>
								</tr>
							</tbody>
						</table>
					</td>';

			$c++;

			$htmlOut .= '
				</tr>
				<tr><td colspan="3"><hr></td></tr>';
		}

		$htmlOut .= '
			</tbody>
		</table>';

		return $htmlOut;
	}
}
