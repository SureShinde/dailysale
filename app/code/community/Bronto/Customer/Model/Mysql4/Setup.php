<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_Mysql4_Setup extends Mage_Customer_Model_Entity_Setup
{
    protected function _getAttributeColumnDefinition($code, $data)
    {
        $definition = parent::_getAttributeColumnDefinition($code, $data);

        if ($code === 'bronto_imported' && is_string($definition)) {
            return 'datetime NULL DEFAULT NULL';
        }

        return $definition;
    }
}
