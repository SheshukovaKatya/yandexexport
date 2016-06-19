<?php

class Badata_YandexExport_Block_Adminhtml_Export extends Mage_Adminhtml_Block_Abstract
{
    public function _toHtml()
    {
     $html = "<div class='content-header'>
            <h3 class='icon-head head-adminhtml-import'>{$this->__('Export products for Yandex market')}</h3>
        </div>
        <div class='entry-edit'>
        <form id='edit_form' action='{$this->getUrl('*/*/export')}' method='post' enctype='multipart/form-data'>
        <div>
            <input type='hidden' name='form_key' value='".Mage::getSingleton('core/session')->getFormKey()."'/>
        </div>
        <div class='entry-edit-head'>
            <h4 class='icon-head head-edit-form fieldset-legend'>{$this->__('Export products')}</h4>
            <div class='form-buttons'></div>
        </div>
        <div class='fieldset' id='base_fieldset'>
            <div class='hor-scroll'>
                <table cellspacing='0' class='form-list'>
                    <tbody>
                    <tr>
                        <td class='label'><label for='import_file'>{$this->__('Export to YML')}</label></td>
                        <td>
                            &nbsp;&nbsp;<input type='submit' value='Export' id='export_yml_to_file'>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </form>
        </div>";
        return $html;
    }
}