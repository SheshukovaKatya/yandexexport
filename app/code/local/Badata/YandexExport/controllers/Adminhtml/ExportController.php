<?php

class Badata_YandexExport_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Index Action, prepare and display form
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('badata_yandexexport');
        $contentBlock = $this->getLayout()->createBlock('badata_yandexexport/adminhtml_export');
        $this->_addContent($contentBlock);
        $this->renderLayout();
    }

    /**
     * Export to csv file
     */
    public function exportAction()
    {
        $filename = Mage::getStoreConfig('badata_yandexexport/general/file_name');//'export_product.xml';
        $content = Mage::helper('badata_yandexexport')->exportYml();
        if(!empty($content)) {
            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($content->asXml());
            if ($this->_prepareDownloadResponse($filename, $dom->saveXML())) {
                Mage::log('Запись завершена в файл ' . $filename, Zend_Log::INFO, 'yml_export.log');
            }
        }
    }
}