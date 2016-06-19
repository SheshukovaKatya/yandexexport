<?php

class Badata_YandexExport_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
    * Time date for specProduct calculate
    */
    private $_tDate = 0;
    private $_optionArray = null;
    private $_storeId = 1;
    private $_rootCategoryId = 2;
    private $_useOtherStoreId = 0;

    /**
     * Prepare data for export to yml file
     */
    public function exportYml()
    {
        Mage::app()->setCurrentStore($this->_storeId);
        $date = date('Y-m-d H:i:s');
        $this->_tDate = strtotime($date);
        $base_path = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $cur_currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $media_path = Mage::getBaseUrl('media') . 'catalog/product';
        $count = 0;

        try {
            $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8'?><!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>" . "<yml_catalog date='{$date}'></yml_catalog>");
            $shop = $xml->addChild('shop');
            $shop->addChild('name', 'vkraske');
            $shop->addChild('company', 'vkraske');
            $shop->addChild('url', $base_path);
            $currencies = $shop->addChild('currencies');
            $currency = $currencies->addChild('currency');
            $currency->addAttribute('id', $cur_currency);
            $currency->addAttribute('rate', '1');
            //$currency->addAttribute('plus', '0');
            $categories = $shop->addChild('categories');
            //$delivery_cost = $shop->addChild('local_delivery_cost');

            $categoriesCollection = Mage::getModel('catalog/category')
                ->setStoreId($this->_storeId)
                ->getCollection()
                ->addAttributeToSelect('*')
                //->addAttributeToFilter('parent_id', array("neq" => 0))
                ->addAttributeToFilter('path', array("like" => "%$this->_rootCategoryId%"))
                ->load();

            $offers = $shop->addChild('offers');

            foreach ($categoriesCollection as $categoryInfo) {

                $category = $categories->addChild('category', $categoryInfo->getName());
                $category->addAttribute('id', $categoryInfo->getId());
                if ($categoryInfo->getId() != $this->_rootCategoryId) {
                    $category->addAttribute('parentId', $categoryInfo->getParentId());
                }
                //When last child category only
                if(!$categoryInfo->getChildren()) {
                    $products = $categoryInfo->setStoreId($this->_storeId)
                        ->getProductCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('type_id', 'simple')
                        ->addAttributeToFilter('price', array("neq" => 0));
                    $products->setPageSize($categoryInfo->getProductCount() + 1);
                    $pages = $products->getLastPageNumber();
                    $currentPage = 1;
                    $product_count = 0;
                    do {
                        $products->setCurPage($currentPage);
                        //$products->load();
                        foreach ($products as $offerInfo) {
                            if($this->_useOtherStoreId) {
                                $key = $offerInfo->getId();
                                $offerInfo = Mage::getModel('catalog/product')
                                    ->setStoreId($this->_useOtherStoreId)
                                    ->load($key);
                            }
                            $offer = $offers->addChild('offer');
                            $offer->addAttribute('id', $offerInfo->getSku());
                            if($offerInfo->isSaleable()) {
                                $offer->addAttribute('available', 'true');
                            }
                            else {
                                $offer->addAttribute('available', 'false');
                            }
                            //$offer->addAttribute('type', 'vendor.model');

                            //OFFER ELEMENTS
                            $offer->addChild('typePrefix', $categoryInfo->getName());
                            $offer->addChild('url', $base_path . $offerInfo->getUrlPath());
                            //Calculate price
                            if ($this->_getSpecPrice($offerInfo->getSpecialFromDate(), $offerInfo->getSpecialToDate(), $offerInfo->getSpecialPrice())) {
                                $offer->addChild('price', round($offerInfo->getSpecialPrice()));
                                $offer->addChild('oldprice', round($offerInfo->getPrice()));
                            } else {
                                $offer->addChild('price', round($offerInfo->getPrice()));
                            }
                            $offer->addChild('currencyId', $cur_currency);
                            $offer->addChild('categoryId', $categoryInfo->getId());
                            if ($offerInfo->getImage()) : $offer->addChild('picture', $media_path . $offerInfo->getImage()); endif;
                            $offer->addChild('name', htmlspecialchars($offerInfo->getName()));
                            if ($offerInfo->getManufacturer()) : $offer->addChild('vendor', $this->_getOptionNameString('manufacturer', $offerInfo->getManufacturer())); endif;
                            $offer->addChild('description', htmlspecialchars($offerInfo->getShortDescription()));
                            if ($offerInfo->getCountryOfManufacture()) : $offer->addChild('country_of_origin', htmlspecialchars($offerInfo->getCountryOfManufacture())); endif;

                            //OFFER "PARAM" ELEMENTS
                            $i = 0;
                            $attributes = $offerInfo->getAttributes();
                            foreach ($attributes as $attribute) {
                                $offerParam = $attribute->getAttributeCode();
                                if ($offerInfo->getData($offerParam) && $offerInfo->getData($offerParam) > 0) {
                                    //attribute: is visible on frontend, is in "product catalog", is custom attribute (created id over 134)
                                    if ($attribute->getIsVisibleOnFront() && $attribute->getEntityTypeId() == 4 && $attribute->getAttributeId() >= 134) {
                                        $param[$product_count][$i] = $offer->addChild('param', htmlspecialchars($attribute->getFrontend()->getValue($offerInfo)))
                                            ->addAttribute('name', htmlspecialchars($attribute->getFrontendLabel()));
                                        $i++;
                                    }
                                }
                            }
                            $product_count++;
                        }
                        $currentPage++;
                        //clear collection and free memory
                        $products->clear();
                    } while ($currentPage <= $pages);
                    $count += $product_count;
                    Mage::log('Категория ' . $categoryInfo->getName() . ' добавлена в очередь(' . $product_count . ' товаров к записи)', Zend_Log::INFO, 'yml_export.log');
                }
            }
        } catch (Exception $e) {
            Mage::log('Произошла ошибка:' . $e, Zend_Log::ERR, 'yml_export.log');
        }
        Mage::log('Всего товаров:' . $count, Zend_Log::INFO, 'yml_export.log');
        return $xml;
    }

    /**
     * Get names from attribute options string values
     * @param string $attributeName
     * @param string $optionString
     * @return null|string
     */
    protected function _getOptionNameString($attributeName, $optionString)
    {
        $this->_getOptionArray($attributeName);
        //search result
        $arrOptions = explode(",", $optionString);
        if (is_array($arrOptions)) {
            foreach ($arrOptions as $option) {
                if (isset($this->_optionArray[$attributeName][$option])) {
                    $result[] = $this->_optionArray[$attributeName][$option];
                }
            }
            $result = implode(", ", $result);
        } else {
            return null;
        }

        return htmlspecialchars($result);
    }

    protected function _initExport() {
        
    }

    protected function _getOptionArray($attributeName, $param = null)
    {
        if (!$this->_optionArray[$attributeName]) {
            $this->_setOptionArray($attributeName);
        }
        if (isset($param)) {
            return $this->_optionArray[$attributeName][$param];
        }
        return $this->_optionArray[$attributeName];
    }

    protected function _setOptionArray($attributeName)
    {
        $attribute = Mage::getModel('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);
        foreach ($attribute->getSource()->getAllOptions(true, true) as $instance) {
            if ($instance['value']) {
                $this->_optionArray[$attributeName][$instance['value']] = $instance['label'];
            }
        }
    }

    protected function _getSpecPrice($from = null, $to = null, $specPrice = null)
    {
        if ($this->_tDate) {
            if (isset($to) && strtotime($to) > $this->_tDate) {
                if (isset($from) && strtotime($from) < $this->_tDate) {
                    if (isset($specPrice) && $specPrice > 0) {
                        return 1;
                    }
                }
            }
        }
        return 0;
    }
}