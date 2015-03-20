<?php

/**
 * @category   Bronto
 * @package    Bronto_Common
 */
class Bronto_Common_Model_System_Config_Source_Message
{
    /**
     * @var array
     */
    protected static $_options = array();

    /**
     * Get Messages as Array of Labels and Values for Select Fields
     *
     * @param null $token
     * @param boolean $useDefault
     * @return array
     */
    public function toOptionArray($token = null, $useDefault = false)
    {
        $helper = Mage::helper('bronto_common');
        $key = empty($token) ? $helper->getApiToken() : $token;
        if (!isset(self::$_options[$key])) {
            self::$_options[$key] = array();
            try {
                if ($api = Mage::helper('bronto_common')->getApi($key)) {
                    /* @var $messageObject Bronto_Api_Message */
                    $messageObject = $api->getMessageObject();
                    foreach ($messageObject->readAll()->iterate() as $message) {
                        $_option = array(
                            'label' => $message->name,
                            'value' => $message->id,
                        );

                        if ($message->status != 'active') {
                            $_option['disabled'] = true;
                        }

                        self::$_options[$key][] = $_option;
                    }
                }
            } catch (Exception $e) {
                $helper->writeError($e);
            }

            array_unshift(self::$_options[$key], array(
                'label' => '',
                'value' => '',
            ));
        }

        if ($useDefault) {
            self::$_options[$key][0]['label'] = $helper->__('-- Use Default --');
        } else {
            self::$_options[$key][0]['label'] = $helper->__('-- None Selected --');
        }

        return self::$_options[$key];
    }
}
