<?php

/**
 * XML Configuration base
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Core_Config_Base
    extends Bronto_Verify_Model_Lib_Varien_Simplexml_Config
{
    /**
     * Constructor
     *
     * @param null $sourceData
     */
    public function __construct($sourceData = null)
    {
        $this->_elementClass = 'Bronto_Verify_Model_Core_Config_Element';
        parent::__construct($sourceData);
    }
}
