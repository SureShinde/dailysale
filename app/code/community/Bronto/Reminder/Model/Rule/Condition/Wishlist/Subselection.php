<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Wishlist_Subselection extends Bronto_Reminder_Model_Condition_Combine_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_wishlist_subselection');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return Mage::getModel('bronto_reminder/rule_condition_wishlist_subcombine')->getNewChildSelectOptions();
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
     * Prepare operator select options
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Wishlist_Subselection
     */
    public function loadOperatorOptions()
    {
        parent::loadOperatorOptions();
        $this->setOperatorOption(array(
            '==' => Mage::helper('bronto_reminder')->__('found'),
            '!=' => Mage::helper('bronto_reminder')->__('not found')
        ));

        return $this;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('If an item is %s in the wishlist with %s of these conditions match:',
            $this->getOperatorElementHtml(),
            $this->getAggregatorElement()->getHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Build query for matching wishlist items
     *
     * @param                                 $rule
     * @param int              | Zend_Db_Expr $website
     *
     * @return Varien_Db_Select
     */
    protected function _prepareConditionsSql($rule, $website)
    {
        $wishlistTable     = $this->getResource()->getTable('wishlist/wishlist');
        $wishlistItemTable = $this->getResource()->getTable('wishlist/item');

        $select = $this->getResource()->createSelect();
        $select->from(array('item' => $wishlistItemTable), array(new Zend_Db_Expr(1)));

        $select->joinInner(
            array('list' => $wishlistTable),
            'item.wishlist_id = list.wishlist_id',
            array()
        );

        $this->_limitByStoreWebsite($select, $website, 'item.store_id');
        $select->where($this->_createCustomerFilter('list.customer_id'));
        $select->limit(1);

        return $select;
    }

    /**
     * Check if validation should be strict
     *
     * @return bool
     */
    protected function _getRequiredValidation()
    {
        return ($this->getOperator() == '==');
    }

    /**
     * Get field names map for subfilter conditions
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return array(
            'product' => 'item.product_id'
        );
    }
}
