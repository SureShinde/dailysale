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

class SFC_Kount_Adminhtml_KountController extends Mage_Adminhtml_Controller_action
{

    /**
     * Index action
     */
    public function indexAction()
    {
        // Nothing to do
    }

    /**
     * What's my ip
     */
    public function whatsMyIpAction()
    {
        // Users Ip address
        $this->getResponse()->setBody(Mage::helper('core/http')->getRemoteAddr());
    }

}