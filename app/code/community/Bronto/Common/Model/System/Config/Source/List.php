<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_System_Config_Source_List
{
    /**
     * @var array
     */
    protected static $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('bronto_common');
        $token = $helper->getApiToken();
        if (!isset(self::$_options[$token])) {
            self::$_options[$token] = array();
            try {
                if ($api = Mage::helper('bronto_common')->getApi($token)) {
                    $listObject = $api->transferMailList();
                    foreach ($listObject->read() as $list) {
                        self::$_options[$token][] = array(
                            'value' => $list->getId(),
                            'label' => $list->getLabel(),
                        );
                    }
                }
            } catch (Exception $e) {
                $helper->writeError('Unable to get List options: ' . $e->getMessage());
            }
        }

        return self::$_options[$token];
    }
}
