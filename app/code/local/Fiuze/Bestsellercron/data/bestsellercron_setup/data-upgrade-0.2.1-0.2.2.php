<?php
$installer = $this;
$installer->startSetup();
$store = Mage::app()->getStore();

//create bestseller category
$parentId = null;
$collection = Mage::getModel('catalog/category')->getCollection()
    ->setStoreId('0')
    ->addAttributeToSelect('name')
    ->addAttributeToSelect('is_active');
foreach ($collection as $cat) {
    if ($cat->getName() == 'Default Category') {
        $parentId = $cat->getId();
        break;
    }
}
if ($parentId) {
    $urlKey = 'bestseller-category';
    $currentCategory = Mage::getModel('catalog/category')->getCollection()
        ->addFieldToFilter('url_key', $urlKey)
        ->setCurPage(1)
        ->setPageSize(1)
        ->getFirstItem();

    if (!($currentCategory && $currentCategory->getId())) {
        $category = Mage::getModel('catalog/category');
        $category->setName('Bestseller Category')
            ->setUrlKey($urlKey)
            ->setIsActive(1)
            ->setDisplayMode('PRODUCTS')
            ->setIsAnchor(0)
            ->setDescription('This is a bestseller category')
            ->setAttributeSetId($category->getDefaultAttributeSetId());

        $parentCategory = Mage::getModel('catalog/category')->load($parentId);
        $category->setPath($parentCategory->getPath());

        $category->save();
        $categoryId = $category->getId();
    }else{
        $categoryId = $currentCategory->getId();
    }
    $config =Mage::getModel('core/config_data')
        ->load(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY, 'path')
        ->setValue($categoryId)
        ->setPath(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY)->cleanModelCache()
        ->save();
    $store->setConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY, $categoryId);

    $rowId = "_1431656231726_726";
    $config =Mage::getModel('core/config_data')
        ->load(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID, 'path')
        ->setValue($rowId)
        ->setPath(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID)->cleanModelCache()
        ->save();
    $store->setConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID, $rowId);

    $value = 'a:1:{s:18:"'.$rowId.'";a:8:{s:8:"category";s:2:"'.$categoryId.'";s:8:"criteria";s:6:"profit";s:11:"time_period";a:3:{i:0;s:2:"00";i:1;s:2:"00";i:2;s:2:"00";}s:11:"days_period";s:1:"1";s:7:"history";s:0:"";s:13:"task_schedule";s:11:"*/2 * * * *";s:18:"number_of_products";s:2:"20";s:8:"checkbox";s:0:"";}}';
    $config =Mage::getModel('core/config_data')
        ->load(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL, 'path')
        ->setValue($value)
        ->setPath(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL)->cleanModelCache()
        ->save();
    $store->setConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL, $value);

    Mage::app()->getCacheInstance()->cleanType('config');
}

$installer->endSetup();

