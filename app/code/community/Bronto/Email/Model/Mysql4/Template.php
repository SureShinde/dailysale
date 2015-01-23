<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Email_Template
{
    /**
     * Initialize email template resource model
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_email/template', 'template_id');
        $this->_setMainTable('core/email_template', 'template_id');
    }

    /**
     * Get Template from original template code and store Id
     *
     * @param string   $templateCode
     * @param int|bool $storeId
     *
     * @return array
     */
    public function loadByOriginalCode($templateCode, $storeId = false)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('core/email_template'))
            ->where("`{$this->getTable('core/email_template')}`.`orig_template_code` = :orig_template_code")
            ->joinLeft(
                $this->getTable('bronto_email/message'),
                "`{$this->getTable('core/email_template')}`.`template_id` = `{$this->getTable('bronto_email/message')}`.`core_template_id`"
            );

        // Filter by store_id if provided
        if ($storeId) {
            $select->where("`{$this->getTable('bronto_email/message')}`.`store_id` = ?", $storeId);
        }

        $result = $this->_getReadAdapter()->fetchRow($select, array('orig_template_code' => $templateCode));

        if (!$result) {
            return array();
        }

        return $result;
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string                   $field
     * @param mixed                    $value
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $field  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where($field . '=?', $value)
            ->joinLeft(
                array($this->getTable('bronto_email/message')),
                "`{$this->getMainTable()}`.`template_id` = `{$this->getTable('bronto_email/message')}`.`core_template_id`"
            );

        return $select;
    }
}
