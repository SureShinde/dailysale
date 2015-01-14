<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_System_Config_Source_Contact_Status
{

    /**
     * Description for protected
     *
     * @var array
     * @access protected
     */
    protected $_options = array();

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return array  Return description (if any) ...
     * @access public
     */
    public function toOptionArray()
    {
        if (!empty($this->_options)) {
            return $this->_options;
        }

        try {
            if ($api = Mage::helper('bronto_common')->getApi()) {
                /* @var $contactObject Bronto_Api_Contact */
                $contactObject = $api->getContactObject();
                foreach ($contactObject->getOptionValues('status') as $status) {
                    $this->_options[] = array(
                        'value' => $status,
                        'label' => $status,
                    );
                }
            }
        } catch (Exception $e) {
            // Ignore
        }

        return $this->_options;
    }
}
