<?php
class TM_NewsletterBooster_Model_Gateway extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/gateway');
    }

    public function getOptionArray()
    {
        return $this->_getResource()->getOptionArray();
    }

}