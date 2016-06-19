<?php

class Badata_YandexExport_Model_Config_Stores
{
    public function toOptionArray() {
        return Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true);
    }
}