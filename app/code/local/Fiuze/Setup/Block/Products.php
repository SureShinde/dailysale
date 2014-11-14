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
    public function getLoadedProductCollection()
    {

        $day = date("j", Mage::getModel('core/date')->timestamp(time()));

        $categoryCollection = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $day);
        $cat_det = $categoryCollection->getData();
        $categoryId = $cat_det[0]["entity_id"];
        $res = Mage::getSingleton('core/resource');
        $eav = Mage::getModel('eav/config');
        $categoryIds = array($categoryId);
        $nameattr = $eav->getAttribute('catalog_category', 'name');
        $nametable = $res->getTableName('catalog/category') . '_' . $nameattr->getBackendType();
        $nameattrid = $nameattr->getAttributeId();
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->joinTable('catalog/category_product',
                'product_id=entity_id', array('single_category_id' => 'category_id', 'position_in_category' => 'position'),
                null, 'left')
            ->joinTable(
                $nametable,
                'entity_id=single_category_id',
                array('single_category_name' => 'value'),
                "attribute_id=$nameattrid",
                'left'
            )
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('single_category_id', array('in' => $categoryIds));

        return $productCollection;
    }
} 