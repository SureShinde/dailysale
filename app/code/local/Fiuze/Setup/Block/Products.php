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
        
        $category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $day);
        $cat_det = $category->getData();
        $categoryId = $cat_det[0]["entity_id"];

        $categoryIds = array($categoryId);

        $productCollection = Mage::getModel('catalog/product')
                         ->getCollection()
                         ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                         ->addAttributeToSelect('*')
                         ->addAttributeToFilter('category_id', array('in' => $categoryIds));        

        return $productCollection;
    }
} 