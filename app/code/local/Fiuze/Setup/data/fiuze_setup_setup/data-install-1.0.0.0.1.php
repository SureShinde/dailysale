<?php
/**
 * Fiuze setup installer
 * 
 * @author Mihail
 */
$logFileName = "fiuzesetup.log";

Mage::log("Fiuze_Setup installation begins to work", null, $logFileName);

$storeId = Mage::app()->getStore()->getId();

$category = Mage::getModel('catalog/category');
$category->setStoreId($storeId);

$rootCategory['name'] = "NewsletterSource";
$rootCategory['path'] = "1";
$rootCategory['display_mode'] = "PRODUCTS";

$rootCategory['include_in_menu'] = 0;

$rootCategory['is_active'] = 0;

$categoryCollection = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $rootCategory['name']);

if($categoryCollection->getSize() == 0 ){
	$category->addData($rootCategory);

	try{
		$category->save();
		$rootCategoryId = $category->getId();

		$smallestDay = 1;

		$largestDay = 31;

		for( $i = $smallestDay; $i <= $largestDay; $i++ ) {
			$newCategory = Mage::getModel('catalog/category');
			$newCategory->setStoreId($storeId);

			$newCategory->setName($i)
				->setParentId($rootCategoryId)
				->setLevel(3)
				->setStoreId($storeId)
				->setAvailableSortBy('name')
				->setDefaultSortBy('name')
				->setIsActive(false)
				->setPosition(0)
				->setPath($category->getPath())
				->save();
		}
		
		Mage::log("Fiuze_Setup installation is successful", null, $logFileName);

	}catch(Exception $e){

		Mage::log("Exception:" . $e->getMessage, null, $logFileName);

	}
}else {
	Mage::log("The category 'NewsletterSource' already exists", null, $logFileName);
}