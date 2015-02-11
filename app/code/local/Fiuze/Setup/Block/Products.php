<?php

/**
 * Fiuze Setup Block
 *
 * @author Mihail, Ron
 */
class Fiuze_Setup_Block_Products extends Mage_Catalog_Block_Product_Abstract implements Mage_Widget_Block_Interface{

    public function __construct(){
        parent::__construct();
        $this->_priceBlockDefaultTemplate = 'fiuze/newslettersubscribe/product/price.phtml';
    }

    /**
     * Retrieve widget html
     *
     * @return string
     */
    protected function _toHtml(){
        $templatePath = $this->getData('set_template');

        //set template using widget options or default template list.phtml
        $this->setTemplate((($templatePath) ? $templatePath : 'fiuze/newslettersubscribe/product/list.phtml'));
        return parent::_toHtml();
    }

    /**
     * Get Product Colloection which will be sent today.
     * @return collection
     */
    public function getLoadedProductCollection(){

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
            ->addAttributeToFilter('visibility', array('in', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)))
            ->addAttributeToFilter('single_category_id', array('in' => $categoryIds))
            ->addAttributeToSort('position_in_category');

        return $productCollection;
    }

    public function getShortenText($text, $chars){

        // Change to the number of characters you want to display
        //$chars = 120;
        $text = $text . " ";
        $text = substr($text, 0, $chars);
        $text = substr($text, 0, strrpos($text, ' '));
        $text = $text . "...";

        return $text;
    }
} 