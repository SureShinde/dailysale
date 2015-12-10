<?php

/**
 * Roundtrip contact creator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Contact_Builder
{
    const EMAIL = 'ps-eng@bronto.com';

    /**
     * API Object for class
     *
     * @var Bronto_Common_Model_Api
     */
    protected $_api;

    /**
     * Instantiate Class and define API Object
     *
     * @param Bronto_Common_Model_Api $api
     *
     * @access public
     */
    public function __construct(Bronto_Api $api)
    {
        $this->_api = $api;
    }

    /**
     * Build Contact
     *
     * @return Bronto_Api_Contact
     * @access protected
     */
    protected function _buildContact()
    {
        /* @var $contactObject Bronto_Api_Contact */
        $contactObject  = $this->_api->getContactObject();
        $contact        = $contactObject->createRow(array());
        $contact->email = self::EMAIL;

        // Get Contact Info
        try {
            $contact->read();
        } catch (Exception $e) {
            $contact->customSource = 'Api';
        }

        // If Test contact exists, remove it and create a new one
        if ($contact->id) {
            $contact->delete($contact->id);

            $contactObject  = $this->_api->getContactObject();
            $contact        = $contactObject->createRow(array());
            $contact->email = self::EMAIL;
        }

        return $contact;
    }

    /**
     * Create a Contact in bronto
     *
     * @return boolean|Bronto_Api_Contact_Row
     * @access public
     */
    public function getContact()
    {
        // Load Contact
        $contact = $this->_buildContact();

        $helper = Mage::helper('bronto_verify/roundtrip');

        // Try to save with new info
        if (!Mage::helper('bronto_common/contact')->saveContact($contact)) {
            $helper->writeDebug('could not save contact');

            return false;
        }

        $helper->writeDebug('Added Contact');

        return $contact;
    }

    public function deleteContact()
    {
    }
}
