<?php

/**
 * Catalog Rule Conditions
 *
 * @category  ProxiBlue
 * @package   DynCatProd
 * @author    Lucas van Staden <sales@proxiblue.com.au>
 * @copyright 2014 Lucas van Staden (ProxiBlue)
 * @license   http://www.proxiblue.com.au/eula EULA
 * @link      http://www.proxiblue.com.au
 */
class ProxiBlue_DynCatProd_Model_Rule_Condition_Product_Conditions_Update
    extends ProxiBlue_DynCatProd_Model_Rule_Condition_Product_Abstract
{


    /**
     * validate
     *
     * @param  Varien_Object $object Quote
     *
     * @return boolean
     */
    public function validate(Varien_Object $object, $updates, $productsToAdd)
    {
        $productsToAdd = array_flip($productsToAdd);
        $object->setData($this->getAttribute(), $this->getValue());
        Mage::getSingleton('catalog/product_action')->updateAttributes(
            $productsToAdd, array($this->getAttribute() => $this->getValue()), $updates->getValue()
        );

        return true;
    }

    public function asHtml()
    {
        $html = Mage::helper('dyncatprod')->__(
            "Set the attribute <strong>%s</strong> to have the value of&nbsp; %s ",
            $this->getAttributeElementHtml(),
            $this->getValueElementHtml()
        );
        $html = $this->getTypeElementHtml()
            . $html
            . $this->getRemoveLinkHtml();

        return $html;
    }

    public function asString($format = '')
    {
        $str = Mage::helper('dyncatprod')->__(
            "Set the attribute <strong>%s</strong> to have the value of&nbsp; %s ",
            $this->getAttributeName(),
            $this->getValueName()
        );

        return $str;
    }


    /**
     * Load attribute options
     *
     * @return Mage_CatalogRule_Model_Rule_Condition_Product
     */
    public function loadAttributeOptions()
    {
        $productAttributes = Mage::getResourceSingleton('catalog/product')
            ->loadAllAttributes()
            ->getAttributesByCode();

        $attributes = array();

        foreach ($productAttributes as $attribute) {
            if ($attribute->getFrontendLabel() == 'dynamic_attributes') {
                continue;
            }
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!$attribute->getFrontendLabel()
                || $attribute->getBackendType() == 'datetime'
                || $attribute->getBackendType() == 'date'
            ) {
                continue;
            }

            $attributes[$attribute->getAttributeCode()]
                = $attribute->getFrontendLabel();
        }

        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getValueElementRenderer()
    {
        if (strpos($this->getValueElementType(), '/') !== false) {
            return Mage::getBlockSingleton($this->getValueElementType());
        }

        return Mage::getBlockSingleton('dyncatprod/rule_editable');
    }

    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        if ($this->getAttribute() === 'is_in_stock'
            || $this->getAttribute() === 'type_id'
            || $this->getAttribute() === 'attribute_set_id'
            || $this->getAttribute() === 'website'
        ) {
            return 'multiselect';
        }
        if ($this->getAttribute() === 'created_at'
            || $this->getAttribute() === 'updated_at'
        ) {
            return 'date';
        }
        if (!is_object($this->getAttributeObject())) {
            return 'text';
        }
        if ($this->getAttribute() === 'is_saleable') {
            return 'select';
        }
        switch ($this->getAttributeObject()->getFrontendInput()) {
            case 'select':
            case 'boolean':
                return 'select';
            case 'multiselect':
                return 'multiselect';
            case 'date':
                return 'date';
            default:
                return 'text';
        }
    }

    /**
     * Retrieve attribute object
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getAttributeObject()
    {
        try {
            $obj = Mage::getSingleton('eav/config')
                ->getAttribute(
                    Mage_Catalog_Model_Product::ENTITY, $this->getAttribute()
                );
        } catch (Exception $e) {
            $obj = new Varien_Object();
            $obj->setEntity(Mage::getResourceSingleton('catalog/product'))
                ->setFrontendInput('text');
        }

        return $obj;
    }

}
