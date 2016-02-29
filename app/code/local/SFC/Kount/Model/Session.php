<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

class SFC_Kount_Model_Session extends Mage_Core_Model_Session_Abstract
{

    public function __construct()
    {
        $namespace = 'sfc_kount_session_data';

        $this->init($namespace);
        Mage::dispatchEvent('sfc_kount_session_init', array('sfc_kount_session' => $this));
    }

    public function incrementKountSessionId()
    {
        // It wasn't already created, lets create a "Kount session ID" by doing an MD5 hash
        $kountSessionId = md5(rand(0, 100000) . '-' . microtime());
        // Now save Kount session ID in Magento / PHP session
        $this->setData('kount_session_id', $kountSessionId);
    }

    public function getKountSessionId()
    {
        // Check if Kount session ID already created for this customer session
        if (!strlen($this->getData('kount_session_id'))) {
            $this->incrementKountSessionId();
        }

        // Return Kount session ID
        return $this->getData('kount_session_id');
    }

}
