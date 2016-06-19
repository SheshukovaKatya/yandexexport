<?php

class Badata_YandexExport_ExportController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index Action, prepare and display form
     */
    public function indexAction()
    {
        $configFile = Mage::getStoreConfig('badata_yandexexport/general/file_name');
        $filename = Mage::getBaseDir('media').DS.$configFile;//'export_product.xml';
        $content = Mage::helper('badata_yandexexport')->exportYml();
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($content->asXml());
        $dom->save($filename);
        Mage::log('Запись завершена в файл '.$filename, Zend_Log::INFO, 'yml_export.log');
    }
}