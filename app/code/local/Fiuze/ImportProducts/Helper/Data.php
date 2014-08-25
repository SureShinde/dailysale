<?php
/**
 * API information helper
 *
 * @author Mihail
 */
class Fiuze_ImportProducts_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * soap client
     *
     * @var string
     */
    const SOAP_CLIENT            = 'importproducts/view/soapclient';

    /**
     * Api user name
     *
     * @var string
     */
    const API_USER     = 'importproducts/view/apiuser';

    /**
     * Api key
     *
     * @var string
     */
    const API_KEY    = 'importproducts/view/apikey';


    /**
     * Checks whether news can be displayed in the frontend
     *
     * @param integer|string|Mage_Core_Model_Store $store
     * @return boolean
     */
    public function isEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $store);
    }

    /**
     * Return soap client information
     *
     * @param integer|string|Mage_Core_Model_Store $store
     * @return string
     */
    public function getSoapClient($store = null)
    {
        return Mage::getStoreConfig(self::SOAP_CLIENT, $store);
    }

    /**
     * Return api user name
     *
     * @param integer|string|Mage_Core_Model_Store $store
     * @return string
     */
    public function getApiUser($store = null)
    {
        return Mage::getStoreConfig(self::API_USER, $store);
    }

    /**
     * Return api key
     *
     * @param integer|string|Mage_Core_Model_Store $store
     * @return string
     */
    public function getApiKey($store = null)
    {
        return Mage::getStoreConfig(self::API_KEY, $store);
    }

    /**
     * Checks if the soapclient, apiuser and apikey exist
     *
     * @param none
     * @return boolean
     */
    public function isExisted($store = null)
    {
		if(($this->getSoapClient() != '') && ($this->getApiUser() != '') && ($this->getApiKey() != '')) {
	        return true;
		} else {
			return false;
		}
    }

}