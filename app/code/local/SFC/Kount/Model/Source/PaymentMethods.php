<?php
 /**
  */
class SFC_Kount_Model_Source_PaymentMethods
{

    public function toOptionArray()
    {

        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $store = Mage::getModel('core/store')->load($code);
        }
        elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store = Mage::app()->getWebsite($website_id)->getDefaultStore();
        }
        else // default level
        {
            $store = 0;
        }

        $methods = array();
        $config = Mage::getStoreConfig('payment', $store);
        foreach ($config as $code => $methodConfig) {
            if (Mage::getStoreConfig('payment/'.$code.'/active', $store) == 1) {
                $methods[$code] = $code;
            }
        }
        $allAvailablePaymentMethods = $methods;        
        
        $config = Mage::getConfig()->loadModulesConfiguration('system.xml')->applyExtends();
        
        $options = array(
            array(
                'value' => '',
                'label' => 'none'
            )
        );

        foreach($allAvailablePaymentMethods as $code => $method) {
            $options[] = array(
                'value' => $code,
                'label' => $code
            );  
        }
        
        return $options;
    }
    
}
