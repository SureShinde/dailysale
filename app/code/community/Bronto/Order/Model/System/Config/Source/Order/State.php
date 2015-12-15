<?php

class Bronto_Order_Model_System_Config_Source_Order_State
{
    private $_states = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $array = array();
        foreach ($this->toArray() as $value => $label) {
            $array[] = array(
                'value' => $value,
                'label' => $label,
            );
        }
        return $array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (!empty($this->_states)) {
            return $this->_states;
        }
        $states = Mage::getModel('sales/order_config')->getNode('states');
        foreach ($states->children() as $state) {
            $this->_states[$state->getName()] = (string) $state->label;
        }
        return $this->_states;
    }
}
