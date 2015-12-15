<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Wishlist_Quantity extends Bronto_Reminder_Model_Condition_Abstract
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_wishlist_quantity');
        $this->setValue(null);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array('value' => $this->getType(),
                     'label' => Mage::helper('bronto_reminder')->__('Number of Items'));
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('Number of wishlist items %s %s ',
            $this->getOperatorElementHtml(), $this->getValueElementHtml())
        . $this->getRemoveLinkHtml();
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
        $wishlistTable     = $this->getResource()->getTable('wishlist/wishlist');
        $wishlistItemTable = $this->getResource()->getTable('wishlist/item');
        $operator          = $this->getResource()->getSqlOperator($this->getOperator());
        $result            = "IF (COUNT(*) {$operator} {$this->getValue()}, 1, 0)";

        $select = $this->getResource()->createSelect();
        $select->from(array('item' => $wishlistItemTable), array(new Zend_Db_Expr($result)));

        $select->joinInner(
            array('list' => $wishlistTable),
            'item.wishlist_id = list.wishlist_id',
            array()
        );

        $this->_limitByStoreWebsite($select, $website, 'item.store_id');
        $select->where($this->_createCustomerFilter('list.customer_id'));

        return $select;
    }
}
