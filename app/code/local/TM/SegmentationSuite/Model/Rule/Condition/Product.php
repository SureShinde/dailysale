<?php

class TM_SegmentationSuite_Model_Rule_Condition_Product extends Mage_Rule_Model_Condition_Product_Abstract
{

    public function validate(Varien_Object $object)
    {
        $product = false;
        if ($object->getProduct() instanceof Mage_Catalog_Model_Product) {
            $product = $object->getProduct();
        } else {
            $product = Mage::getModel('catalog/product')
                ->load($object->getProductId());
        }

         $object->setQty($object->getQtyOrdered());

        $product
//             ->setQty($object->getQtyOrdered())
            ->setQuoteItemQty($object->getOrderedQty())
            ->setQuoteItemPrice($object->getPrice()) // possible bug: need to use $object->getBasePrice()
            ->setQuoteItemRowTotal($object->getBaseRowTotal());

        return parent::validate($product);
    }
}
