<?php

class TM_NewsletterBooster_Model_Mysql4_Gateway extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the id refers to the key field in your database table.
        $this->_init('newsletterbooster/gateway', 'id');
    }

    public function getOptionArray()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ;

        $rowset = array('0' => Mage::helper('newsletterbooster')->__('Store Mail'));
        $rowset['-1'] = Mage::helper('newsletterbooster')->__('Mandrill Api');
        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            $rowset[$row['id']] = $row['name'];
        }

        return $rowset;
    }
}