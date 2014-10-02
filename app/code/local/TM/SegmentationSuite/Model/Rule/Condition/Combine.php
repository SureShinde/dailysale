<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 * 
 * EasyBanner module for Magento - flexible banner management
 * 
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_SegmentationSuite_Model_Rule_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setType('segmentationsuite/rule_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $addressCondition = Mage::getModel('segmentationsuite/rule_condition_address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();
        $addressAtt = array();
        foreach ($addressAttributes as $code=>$label) {
            $addressAtt[] = array('value'=>'segmentationsuite/rule_condition_address|'.$code, 'label'=>$label);
        }
        
        $customersConditions = Mage::getModel('segmentationsuite/rule_condition_customer')
            ->loadAttributeOptions()
            ->getAttributeOption();
        
        $attributes = array();
        foreach ($customersConditions as $code => $label) {
            $attributes[] = array(
                'value'=>'segmentationsuite/rule_condition_customer|' . $code,
                'label' => $label
            );
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array('value' => 'segmentationsuite/rule_condition_combine',
                 'label' => Mage::helper('segmentationsuite')->__('Conditions Combination')
            ),
            array('value' => 'segmentationsuite/rule_condition_product_subselect',
                 'label' => Mage::helper('segmentationsuite')->__('Order Product Attribute')
            ),
            array('value' => $addressAtt, 'label' => Mage::helper('segmentationsuite')->__('Address')),
            array('value' => $attributes, 'label' => Mage::helper('segmentationsuite')->__('Customer'))
        ));
        return $conditions;
    }

    public function collectValidatedAttributes($customerCollection)
    {
        foreach ($this->getConditions() as $condition) {
            if ($condition instanceof TM_SegmentationSuite_Model_Rule_Condition_Customer) {
                $condition->collectValidatedAttributes($customerCollection);
            }
        }
        
        return $this;
    }
}
