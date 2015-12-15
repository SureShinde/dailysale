<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
abstract class Bronto_Reminder_Model_Condition_Combine_Abstract extends Mage_Rule_Model_Condition_Combine
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
     * Add operator when loading array
     *
     * @param array  $arr
     * @param string $key
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Combine
     */
    public function loadArray($arr, $key = 'conditions')
    {
        if (isset($arr['operator'])) {
            $this->setOperator($arr['operator']);
        }

        if (isset($arr['attribute'])) {
            $this->setAttribute($arr['attribute']);
        }

        return parent::loadArray($arr, $key);
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
     * Get filter by customer condition for rule matching sql
     *
     * @param string $fieldName
     *
     * @return string
     */
    protected function _createCustomerFilter($fieldName)
    {
        return "{$fieldName} = root.customer_id";
    }

    /**
     * Build query for matching customer to rule condition
     *
     * @param $rule
     * @param $website
     *
     * @return Varien_Db_Select
     */
    protected function _prepareConditionsSql($rule, $website)
    {
        $select = $this->getResource()->createSelect();
        $table  = $this->getResource()->getTable('customer/entity');
        $select->from($table, array(new Zend_Db_Expr(1)));
        $select->where($this->_createCustomerFilter('entity_id'));

        return $select;
    }

    /**
     * Check if condition is required. It affect condition select result comparison type (= || <>)
     *
     * @return bool
     */
    protected function _getRequiredValidation()
    {
        return ($this->getValue() == 1);
    }

    /**
     * Get SQL select for matching customer to rule condition
     *
     * @param $rule
     * @param $website
     *
     * @return Varien_Db_Select
     */
    public function getConditionsSql($rule, $website)
    {
        /**
         * Build base SQL
         */
        $select        = $this->_prepareConditionsSql($rule, $website);
        $required      = $this->_getRequiredValidation();
        $whereFunction = ($this->getAggregator() == 'all') ? 'where' : 'orWhere';
        $operator      = $required ? '=' : '<>';
        //$operator       = '=';

        $gotConditions = false;

        /**
         * Add children subselects conditions
         */
        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSql($rule, $website)) {
                $criteriaSql = "(IFNULL(($sql), 0) {$operator} 1)";
                $select->$whereFunction($criteriaSql);
                $gotConditions = true;
            }
        }

        /**
         * Process combine subfilters. Subfilters are part of base select which can be affected by children.
         */
        $subfilterMap = $this->_getSubfilterMap();
        if ($subfilterMap) {
            foreach ($this->getConditions() as $condition) {
                $subfilterType = $condition->getSubfilterType();
                if (isset($subfilterMap[$subfilterType])) {
                    $subfilter = $condition->getSubfilterSql($subfilterMap[$subfilterType], $required, $website);
                    if ($subfilter) {
                        $select->$whereFunction($subfilter);
                        $gotConditions = true;
                    }
                }
            }
        }

        if (!$gotConditions) {
            $select->where('1=1');
        }

        return $select;
    }

    /**
     * Get information about subfilters map. Map contain children condition type and associated
     * column name from itself select.
     * Example: array('my_subtype'=>'my_table.my_column')
     * In practice - date range can be as subfilter for different types of condition combines.
     * Logic of this filter apply is same - but column names different
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return array();
    }

    /**
     * Limit select by website with joining to store table
     *
     * @param Zend_Db_Select   $select
     * @param int|Zend_Db_Expr $website
     * @param string           $storeIdField
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

    /**
     * Getter for "Conditions Combination" select option for recursive combines
     */
    protected function _getRecursiveChildSelectOption()
    {
        if (method_exists('Mage_Rule_Model_Condition_Combine', '_getRecursiveChildSelectOption')) {
            return parent::_getRecursiveChildSelectOption();
        }

        return array('value' => $this->getType(), 'label' => Mage::helper('rule')->__('Conditions Combination'));
    }
}
