<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Cart_Sku extends Bronto_Reminder_Model_Condition_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_cart_sku');
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
                     'label' => Mage::helper('bronto_reminder')->__('SKU'));
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('Item SKU %s %s ',
            $this->getOperatorElementHtml(), $this->getValueElementHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Initialize value select options
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption(Mage::getSingleton('adminhtml/system_store')->getStoreOptionHash());

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
        $quoteTable     = $this->getResource()->getTable('sales/quote');
        $quoteItemTable = $this->getResource()->getTable('sales/quote_item');
        $operator       = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(array('item' => $quoteItemTable), array(new Zend_Db_Expr(1)));

        $select->joinInner(
            array('quote' => $quoteTable),
            'item.quote_id = quote.entity_id',
            array()
        );

        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
        $select->where('quote.is_active = 1');
        $select->where("item.sku {$operator} ?", $this->getValue());
        $select->where('quote.entity_id = root.quote_id');
        $select->limit(1);

        return $select;
    }
}
