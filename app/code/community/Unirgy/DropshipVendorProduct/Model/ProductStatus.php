<?php

class Unirgy_DropshipVendorProduct_Model_ProductStatus extends Mage_Catalog_Model_Product_Status
{
    const STATUS_PENDING    = 3;
    const STATUS_FIX        = 4;
    const STATUS_DISCARD    = 5;
    const STATUS_VACATION   = 6;
    const STATUS_SUSPENDED   = 7;
    static public function getOptionArray()
    {
        $res = array(
            self::STATUS_ENABLED    => Mage::helper('catalog')->__('Enabled'),
            self::STATUS_DISABLED   => Mage::helper('catalog')->__('Disabled'),
            self::STATUS_PENDING    => Mage::helper('catalog')->__('Pending'),
            self::STATUS_FIX        => Mage::helper('catalog')->__('Fix'),
            self::STATUS_DISCARD    => Mage::helper('catalog')->__('Discard'),
            self::STATUS_VACATION   => Mage::helper('catalog')->__('Vacation')
        );
        if (Mage::helper('udropship')->isModuleActive('Unirgy_DropshipVendorMembership')) {
            $res[self::STATUS_SUSPENDED] = Mage::helper('catalog')->__('Suspended');
        }
        return $res;
    }
    static public function getAllOptions()
    {
        $res = array(
            array(
                'value' => '',
                'label' => Mage::helper('catalog')->__('-- Please Select --')
            )
        );
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
               'value' => $index,
               'label' => $value
            );
        }
        return $res;
    }
}