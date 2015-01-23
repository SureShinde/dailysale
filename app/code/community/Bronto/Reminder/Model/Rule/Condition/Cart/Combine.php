<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Cart_Combine extends Bronto_Reminder_Model_Condition_Combine_Abstract
{
    /**
     * Initialize model
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_cart_combine');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $prefix = 'bronto_reminder/rule_condition_cart_';

        return array_merge_recursive(
            parent::getNewChildSelectOptions(), array(
                $this->_getRecursiveChildSelectOption(),
                Mage::getModel("{$prefix}couponcode")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}itemsquantity")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}totalquantity")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}virtual")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}amount")->getNewChildSelectOptions(),
                array( // subselection combo
                    'value' => 'bronto_reminder/rule_condition_cart_subselection',
                    'label' => Mage::helper('bronto_reminder')->__('Items Subselection')
                )
            )
        );
    }
}
