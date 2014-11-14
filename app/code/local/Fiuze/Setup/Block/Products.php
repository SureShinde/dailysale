<?php
/**
 * Fiuze Setup Block
 *
 * @author Mihail, Ron
 */
class Fiuze_Setup_Block_Products extends Mage_Catalog_Block_Product_Abstract implements Mage_Widget_Block_Interface
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('fiuze/newslettersubscribe/product/list.phtml');
        
        $this->_priceBlockDefaultTemplate = 'fiuze/newslettersubscribe/product/price.phtml';
    }
    
    /**
    * Get Product Colloection which will be sent today.
    * @return collection
    */
    public function getLoadedProductCollection() {
        
        $day = date("j", Mage::getModel('core/date')->timestamp(time()));

        $categoryCollection = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $day);
        $cat_det = $categoryCollection->getData();
        $categoryId = $cat_det[0]["entity_id"];

        $categoryIds = array($categoryId);

        $productCollection = Mage::getModel('catalog/category')->load((int)$categoryId)
            ->getProductCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToSort('position');

        return $productCollection;
    }
} 