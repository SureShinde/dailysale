<?php

class Bronto_Verify_Model_System_Config_Source_Wsdlcache
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

        $this->_options = array(
            'WSDL_CACHE_BOTH'   => 'WSDL_CACHE_BOTH',
            'WSDL_CACHE_NONE'   => 'WSDL_CACHE_NONE',
            'WSDL_CACHE_DISK'   => 'WSDL_CACHE_DISK',
            'WSDL_CACHE_MEMORY' => 'WSDL_CACHE_MEMORY',
        );

        return $this->_options;
    }
}
