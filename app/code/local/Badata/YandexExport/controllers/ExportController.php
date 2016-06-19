<?php

class Badata_YandexExport_ExportController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index Action, prepare and display form
     */
    public function indexAction()
    {
        $filename = Mage::getBaseDir('media').DS.'export_product.xml';
        $content = Mage::helper('badata_yandexexport')->exportYml();
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($content->asXml());
        $dom->save($filename);
        Mage::log('Запись завершена в файл '.$filename, Zend_Log::INFO, 'yml_export.log');
    }
}