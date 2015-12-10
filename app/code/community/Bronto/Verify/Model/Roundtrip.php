<?php

/**
 * API Roundtrip Validate Model
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Roundtrip
{
    /**
     * Helper Object for class
     *
     * @var
     */
    private $_helper;

    /**
     * Instantiate class and create helper
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('bronto_verify/roundtrip');
    }

    /**
     * @return array
     * @access public
     */
    public function processRoundtrip()
    {
        $status = true;

        // Ping WSDL
        ob_start();

        // Commented out due to a 500 error when not requesting body
        //	curl_setopt($ch, CURLOPT_NOBODY, true); // don't return body, just return header

        $ch = curl_init(Bronto_Api::BASE_WSDL);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        ob_end_clean();

        // Set setting and return results
        // If the token is valid, we can safely assume the API is up
        if (200 == $retcode || Mage::helper('bronto_verify/apitoken')->getStatus()) {
            $this->_helper->writeDebug('Connection status SUCCESS');
            $this->_helper->setStatus($this->_helper->getPath('roundtrip_status'), '1');
        } else {
            $this->_helper->writeDebug('Connection status FAILURE');
            $this->_helper->setStatus($this->_helper->getPath('roundtrip_status'), '0');
            $status = false;
        }

        return $status;
    }
}
