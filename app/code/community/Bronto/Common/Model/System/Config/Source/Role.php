<?php

class Bronto_Common_Model_System_Config_Source_Role
{

    /**
     * @var array
     */
    protected $_options;

    /**
     * Supporting role key => value pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!is_null($this->_options)) {
            return $this->_options;
        }

        $helper         = Mage::helper('bronto_common');
        $this->_options = array(
            'developer' => $helper->__('Developer'),
            'market'    => $helper->__('Marketer'),
            'partner'   => $helper->__('Solution Partner'),
        );

        return $this->_options;
    }
}
