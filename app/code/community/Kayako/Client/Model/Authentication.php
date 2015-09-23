<?php
/**
 * Copyright (c) 2013 Kayako
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 *OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Authentication Model
 */
set_include_path(get_include_path() . PS . Mage::getBaseDir('lib') . DS . 'KayakoAPI');
require_once('kyIncludes.php');

class Kayako_Client_Model_Authentication extends Varien_Object
{
	private $_apiKey;
	private $_secretKey;
	private $_apiURL;

	/**
	 * Initialize API connection
	 */
	public function init()
	{
		$_config = new kyConfig($this->getApiURL(), $this->getApiKey(), $this->getSecretKey());
		$_config->setIsStandardURLType(true);
		$_config->setDebugEnabled(true);
		kyConfig::set($_config);
	}

	/**
	 * Retrieve API key
	 *
	 * @return string
	 */
	private function getApiKey()
	{
		$this->_apiKey = Mage::getStoreConfig('client/settings/api_key');

		return $this->_apiKey;
	}

	/**
	 * Retrieve Secret Key
	 *
	 * @return string
	 */
	private function getSecretKey()
	{
		$this->_secretKey = Mage::getStoreConfig('client/settings/secret_key');

		return $this->_secretKey;
	}

	/**
	 * Retrieve API URL
	 *
	 * @return string
	 */
	private function getApiURL()
	{
		$this->_apiURL = Mage::getStoreConfig('client/settings/api_url');

		return $this->_apiURL;
	}

	/**
	 * Returns Varien data collection
	 *
	 * @param array $items
	 *
	 * @return Varien_Data_Collection
	 */
	public function getVarienDataCollection($items)
	{
		$collection = new Varien_Data_Collection();
		foreach ($items as $item) {
			$_Varien = new Varien_Object();
			$_Varien->setData($item);
			$collection->addItem($_Varien);
		}

		return $collection;
	}
}