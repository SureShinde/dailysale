<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProxiBlue_DynCatProd_Model_Convert_Adapter_Category
    extends Mage_Dataflow_Model_Convert_Adapter_Abstract
{
    protected $_categoryModel;

    public function load()
    {
        return $this;
    }

    public function save()
    {
        return $this;
    }

    /**
     * Retrieve category model cache
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getCategoryModel()
    {
        if (is_null($this->_categoryModel)) {
            $categoryModel = Mage::getModel('catalog/category');
            $this->_categoryModel = Mage::objects()->save($categoryModel);
        }
        return Mage::objects()->load($this->_categoryModel);
    }


    public function saveRow(array $importData)
    {

        $requiredFields = array(
            'store',
            'category_id',
            'dynamic_attributes',
            'parent_dynamic_attributes',
            'ignore_parent_dynamic'
        );

        //Make sure required fields exist
        if (!$this->checkFieldsExist($requiredFields, $importData)) {
            return false;
        }
        $category = $this-> getCategoryModel()->load($importData['category_id']);
        if (!$category->getId()) {
            $message = Mage::helper('dataflow')->__(
                'Skip import row, category with ID "%s" does not exist!', $importData['category_id']
            );
            Mage::throwException($message);
        }
        unset($importData['category_name']);
        unset($importData['store']);
        $category->addData($importData);
        $category->save();
    }

    protected function checkFieldsExist(array $fields, array $importData)
    {
        $rowValid = true;
        foreach ($fields as $field) {
            if (!isset($importData[$field])) {
                $message = Mage::helper('dataflow')->__('Skip import row, required field "%s" not defined', $field);
                $rowValid = false;
                Mage::throwException($message);
            }
        }

        return $rowValid;
    }
}
