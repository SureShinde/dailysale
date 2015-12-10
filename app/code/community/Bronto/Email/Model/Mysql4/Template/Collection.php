<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Mysql4_Template_Collection extends Mage_Core_Model_Mysql4_Email_Template_Collection
{
    protected $_brontoTable;

    public function _construct()
    {
        parent::_construct();
        $this->_templateTable = $this->getResource()->getTable('bronto_email/template');
        $this->_brontoTable   = $this->getResource()->getTable('bronto_email/message');
        if (Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(4, array('edition' => 'Professional', 'major' => 9)))) {
            $this->_select->joinLeft(
                array($this->_brontoTable),
                "`{$this->_templateTable}`.`template_id` = `{$this->_brontoTable}`.`core_template_id`"
            );
        }
    }

    /**
     * Adds the store filtering based on All Store views or a specific one
     *
     * @param int $storeId
     * @return Bronto_Email_Model_Mysql4_Template_Collection
     */
    public function addStoreViewFilter($storeId)
    {
        return $this->addFieldToFilter('store_id', array(
            'in' => array('0', $storeId))
        );
    }

    /**
     * Init collection select
     *
     * @return Bronto_Email_Model_Mysql4_Template_Collection
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(array('main_table' => $this->getMainTable()))
            ->joinLeft(
                array($this->_brontoTable),
                "`main_table`.`template_id` = `{$this->_brontoTable}`.`core_template_id`"
            );

        return $this;
    }
}
