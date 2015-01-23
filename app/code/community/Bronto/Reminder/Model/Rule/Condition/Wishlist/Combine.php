<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Wishlist_Combine extends Bronto_Reminder_Model_Condition_Combine_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_wishlist_combine');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $prefix = 'bronto_reminder/rule_condition_wishlist_';

        return array_merge_recursive(
            parent::getNewChildSelectOptions(), array(
                $this->_getRecursiveChildSelectOption(),
                Mage::getModel("{$prefix}sharing")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}quantity")->getNewChildSelectOptions(),
                array( // subselection combo
                    'value' => 'bronto_reminder/rule_condition_wishlist_subselection',
                    'label' => Mage::helper('bronto_reminder')->__('Items Subselection')
                )
            )
        );
    }
}
