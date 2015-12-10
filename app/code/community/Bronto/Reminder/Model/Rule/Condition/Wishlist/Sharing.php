<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Wishlist_Sharing extends Bronto_Reminder_Model_Condition_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_wishlist_sharing');
        $this->setValue(1);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array('value' => $this->getType(),
                     'label' => Mage::helper('bronto_reminder')->__('Sharing'));
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('Wishlist %s shared',
            $this->getValueElementHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Get element type for value select
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Init list of available values
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Wishlist_Sharing
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array(
            '1' => Mage::helper('bronto_reminder')->__('is'),
            '0' => Mage::helper('bronto_reminder')->__('is not'),
        ));

        return $this;
    }

    /**
     * Get SQL select
     *
     * @param                                 $rule
     * @param int              | Zend_Db_Expr $website
     *
     * @return Varien_Db_Select
     */
    public function getConditionsSql($rule, $website)
    {
        $table = $this->getResource()->getTable('wishlist/wishlist');

        $select = $this->getResource()->createSelect();
        $select->from(array('list' => $table), array(new Zend_Db_Expr(1)));
        $select->where("list.shared = ?", $this->getValue());
        $select->where($this->_createCustomerFilter('list.customer_id'));
        $select->limit(1);

        return $select;
    }
}
