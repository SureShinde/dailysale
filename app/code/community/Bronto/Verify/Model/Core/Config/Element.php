<?php

/**
 * XML Configuration element
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Model_Core_Config_Element
    extends Bronto_Verify_Model_Lib_Varien_Simplexml_Element
{
    /**
     * Is element enabled
     *
     * @param string  $var
     * @param boolean $value
     *
     * @return boolean
     * @access public
     */
    public function is($var, $value = true)
    {
        $flag = $this->$var;

        if ($value === true) {
            $flag = strtolower((string)$flag);
            if (!empty($flag) && 'false' !== $flag && 'off' !== $flag) {
                return true;
            } else {
                return false;
            }
        }

        return !empty($flag) && (0 === strcasecmp($value, (string)$flag));
    }

    /**
     * Get node class name
     *
     * @return string
     * @access public
     */
    public function getClassName()
    {
        if ($this->class) {
            $model = (string)$this->class;
        } elseif ($this->model) {
            $model = (string)$this->model;
        } else {
            return false;
        }

        return Mage::getConfig()->getModelClassName($model);
    }
}
