<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Condition_Abstract extends Mage_Rule_Model_Condition_Abstract
{
    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = array('==', '!=', '>=', '>', '<=', '<');
            $this->_defaultOperatorInputByType['string']  = array('==', '!=', '{}', '!{}');
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Get condition combine resource model
     *
     * @return Bronto_Reminder_Model_Mysql4_Rule
     */
    public function getResource()
    {
        return Mage::getResourceSingleton('bronto_reminder/rule');
    }

    /**
     * Generate customer condition string
     *
     * @param $fieldName
     *
     * @return string
     */
    protected function _createCustomerFilter($fieldName)
    {
        return "{$fieldName} = root.customer_id";
    }

    /**
     * Limit select by website with joining to store table
     *
     * @param Zend_Db_Select                                                      $select
     * @param int                                      |             Zend_Db_Expr $website
     * @param string                                                              $storeIdField
     *
     * @return Bronto_Reminder_Model_Condition_Abstract
     */
    protected function _limitByStoreWebsite(Zend_Db_Select $select, $website, $storeIdField)
    {
        $storeTable = $this->getResource()->getTable('core/store');
        $select->join(array('store' => $storeTable), $storeIdField . '=store.store_id', array())
            ->where('store.website_id=?', $website);

        return $this;
    }
}
