<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Rule_Condition_Cart extends Bronto_Reminder_Model_Condition_Combine_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('bronto_reminder/rule_condition_cart');
        $this->setValue(null);
    }

    /**
     * Get list of available subconditions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return Mage::getModel('bronto_reminder/rule_condition_cart_combine')->getNewChildSelectOptions();
    }

    /**
     * Get input type for attribute value
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Override parent method
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Cart
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array());

        return $this;
    }

    /**
     * Prepare operator select options
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Cart
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(array(
            '==' => Mage::helper('rule')->__('for'),
            //            '>'  => Mage::helper('rule')->__('for greater than'),
            //            '>=' => Mage::helper('rule')->__('for or greater than'),
            //            '<'  => Mage::helper('rule')->__('for less than'),
            //            '<=' => Mage::helper('rule')->__('for or less than'),
        ));

        return $this;
    }

    /**
     * Return required validation
     *
     * @return true
     */
    protected function _getRequiredValidation()
    {
        return true;
    }

    /**
     * Init available options list
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Cart_Amount
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'days'    => Mage::helper('bronto_reminder')->__('days'),
            'hours'   => Mage::helper('bronto_reminder')->__('hours'),
            'minutes' => Mage::helper('bronto_reminder')->__('minutes')
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
        . Mage::helper('bronto_reminder')->__('Shopping cart is not empty and abandoned for %s %s and %s of these conditions match:',
            //                $this->getOperatorElementHtml(),
            $this->getValueElementHtml(),
            $this->getAttributeElementHtml(),
            $this->getAggregatorElement()->getHtml())
        . $this->getRemoveLinkHtml();
    }

    /**
     * Get condition SQL select
     *
     * @param                  $rule
     * @param int|Zend_Db_Expr $website
     *
     * @return Varien_Db_Select
     */
    protected function _prepareConditionsSql($rule, $website)
    {
        $attributeValue = strtolower($this->getAttribute());
        $conditionValue = (int)$this->getValue();
        $requiredValue  = 1;

        if ($conditionValue <= 0) {
            Mage::throwException(Mage::helper('bronto_reminder')->__('Root shopping cart condition should have %s value greater than 0.', $attributeValue));
        }

        $table = $this->getResource()->getTable('sales/quote');

        $select = $this->getResource()->createSelect();
        $select->from(array('quote' => $table), array(new Zend_Db_Expr(1)));

        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');


        // Handle date and interval conditions
        if ('hours' == $attributeValue) {
            $conditionValue *= 60;
        } elseif ('days' == $attributeValue) {
            $conditionValue *= 1440;
        } else {
            $requiredValue = 30;
        }
        if ($conditionValue < $requiredValue) {
            Mage::throwException(Mage::helper('bronto_reminder')->__('Root shopping cart condition should have %s value at least %d.', $attributeValue, $requiredValue));
        }

        $curDate = now();
        $select->where("quote.updated_at BETWEEN DATE_SUB('$curDate', INTERVAL (?+:interval) MINUTE) AND DATE_SUB('$curDate', INTERVAL ? MINUTE)", $conditionValue);

        // Handle standard 
        $select->where('quote.is_active = 1');
        $select->where('quote.items_count > 0');
        $select->where('quote.entity_id = root.quote_id');
        $select->limit(1);

        return $select;
    }

    /**
     * Get base SQL select
     *
     * @param                  $rule
     * @param int|Zend_Db_Expr $website
     *
     * @return Varien_Db_Select
     */
    public function getConditionsSql($rule, $website)
    {
        $select     = $this->_prepareConditionsSql($rule, $website);
        $required   = $this->_getRequiredValidation();
        $aggregator = ($this->getAggregator() == 'all') ? ' AND ' : ' OR ';
        $operator   = $required ? '=' : '<>';
        $conditions = array();

        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSql($rule, $website)) {
                $conditions[] = "(IFNULL(($sql), 0) {$operator} 1)";
            }
        }

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        return $select;
    }
}
