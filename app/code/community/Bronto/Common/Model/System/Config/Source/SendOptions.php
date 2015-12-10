<?php

class Bronto_Common_Model_System_Config_Source_SendOptions
{
    private $_options;

    private static $_optionToFlag = array(
        3 => 'replyTracking',
        2 => 'fatigueOverride',
        1 => 'authentication',
    );

    /**
     * Retrieve option values from bit masked values
     *
     * @return array
     */
    public function toArray()
    {
        if (empty($this->_options)) {
            $helper = Mage::helper('bronto_common');
            $this->_options = array(
                1 => $helper->__('Sender Authentication'),
                2 => $helper->__('Fatigue Override'),
                3 => $helper->__('Reply Tracking'),
            );
            $concatValues = array();
            $allValues = 0;
            $allLabels = '';
            foreach ($this->_options as $key => $label) {
                $allValues = $allValues === 0 ? $key : $allValues << $key;
                if ($label == end($this->_options)) {
                    $concatValues[$allValues] = $helper->__($allLabels . 'and ' . $label);
                } else {
                    foreach (range($key + 1, count($this->_options)) as $number) {
                        $concatValues[$key << $number] = $helper->__(implode(' and ', array($this->_options[$key], $this->_options[$number])));
                    }
                    $allLabels .= $label . ', ';
                }
            }
            $this->_options += $concatValues;
        }
        return $this->_options;
    }

    /**
     * Retrieve config options for sending options
     *
     * @return array
     */
    public function toOptionArray($default = false)
    {
        $helper = Mage::helper('bronto_common');
        $options = array();
        foreach ($this->toArray() as $value => $label) {
            $options[] = array(
                'value' => $value,
                'label' => $label
            );
        }

        $firstOption = array(
            'label' => $helper->__($default ? '-- Use Default -- ' : '-- None Selected --'),
            'value' => 0
        );
        array_unshift($options, $firstOption);
        return $options;
    }

    /**
     * Sets the send flags based on masked value
     *
     * @param Bronto_Api_Delivery_Row $delivery
     * @param int $optionValue
     * @return boolean
     */
    public function setDeliveryFlags($delivery, $optionValue)
    {
        if (empty($optionValue)) {
            return false;
        }

        if (array_key_exists($optionValue, self::$_optionToFlag)) {
            $delivery->{self::$_optionToFlag[$optionValue]} = true;
            return true;
        }

        foreach (self::$_optionToFlag as $value => $flag) {
            $testValue = $optionValue >> $value;
            if ($testValue != 0 || $optionValue == $value) {
                $delivery->{$flag} = true;
                $optionValue = $testValue;
            }
        }
        return true;
    }
}
