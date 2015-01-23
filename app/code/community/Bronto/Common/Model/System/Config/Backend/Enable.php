<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_System_Config_Backend_Enable
    extends Mage_Core_Model_Config_Data
{

    protected $_eventPrefix = 'bronto_enable';

    /**
     * @return Bronto_Common_Model_System_Config_Backend_Enable
     */
    protected function _beforeSave()
    {
        if ($this->isValueChanged()) {
            // Build Event from section and method
            $pathParts = explode('/', $this->getPath());
            $section   = array_shift($pathParts);
            $method    = ($this->getValue() == "0") ? 'disable' : 'enable';
            $event     = $section . '_' . $method;

            // Trigger section/method specific event
            Mage::dispatchEvent($event, $this->getData());
        }

        return parent::_beforeSave();
    }
}
