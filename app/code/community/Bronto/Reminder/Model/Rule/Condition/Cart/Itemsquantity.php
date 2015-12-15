<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Cart_Itemsquantity extends Bronto_Reminder_Model_Condition_Abstract
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_cart_itemsquantity');
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
                     'label' => Mage::helper('bronto_reminder')->__('Cart Line Items'));
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('Number of shopping cart line items %s %s:',
            $this->getOperatorElementHtml(), $this->getValueElementHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Get SQL select for matching shopping cart items count
     *
     * @param                                 $rule
     * @param int              | Zend_Db_Expr $website
     *
     * @return Varien_Db_Select
     */
    public function getConditionsSql($rule, $website)
    {
        $table    = $this->getResource()->getTable('sales/quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(array('quote' => $table), array(new Zend_Db_Expr(1)));

        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
        $select->where('quote.is_active = 1');
        $select->where("quote.items_count {$operator} ?", $this->getValue());
        $select->where('quote.entity_id = root.quote_id');
        $select->limit(1);

        return $select;
    }
}
