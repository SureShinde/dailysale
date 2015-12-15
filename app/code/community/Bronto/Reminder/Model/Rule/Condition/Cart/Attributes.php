<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Cart_Attributes extends Bronto_Reminder_Model_Condition_Abstract
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_cart_attributes');
        $this->setValue(null);
    }

    /**
     * Get information for being presented in condition list
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array('value' => $this->getType(),
                     'label' => Mage::helper('bronto_reminder')->__('Numeric Attribute'));
    }

    /**
     * Init available options list
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Cart_Attributes
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'weight'     => Mage::helper('bronto_reminder')->__('weight'),
            'row_weight' => Mage::helper('bronto_reminder')->__('row weight'),
            'qty'        => Mage::helper('bronto_reminder')->__('quantity'),
            'price'      => Mage::helper('bronto_reminder')->__('base price'),
            'base_cost'  => Mage::helper('bronto_reminder')->__('base cost')
        ));

        return $this;
    }

    /**
     * Condition string on conditions page
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
        . Mage::helper('bronto_reminder')->__('Item %s %s %s:',
            $this->getAttributeElementHtml(), $this->getOperatorElementHtml(), $this->getValueElementHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Build condition limitations sql string for specific website
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

        switch ($this->getAttribute()) {
            case 'weight':
                $field = 'item.weight';
                break;
            case 'row_weight':
                $field = 'item.row_weight';
                break;
            case 'qty':
                $field = 'item.qty';
                break;
            case 'price':
                $field = 'item.price';
                break;
            case 'base_cost':
                $field = 'item.base_cost';
                break;
            default:
                Mage::throwException(Mage::helper('bronto_reminder')->__('Unknown attribute specified'));
        }

        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
        $select->where('quote.is_active = 1');
        $select->where("{$field} {$operator} ?", $this->getValue());
        $select->where('quote.entity_id = root.quote_id');
        $select->limit(1);

        return $select;
    }
}
