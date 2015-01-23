<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Wishlist_Subcombine extends Bronto_Reminder_Model_Condition_Combine_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_wishlist_subcombine');
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
                Mage::getModel("{$prefix}storeview")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}attributes")->getNewChildSelectOptions()
            )
        );
    }
}
