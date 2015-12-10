<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Mysql4_Setup extends Mage_Sales_Model_Mysql4_Setup
{

    /**
     * Get column definition for attribute
     *
     * @param string $code Parameter description (if any) ...
     * @param mixed  $data Parameter description (if any) ...
     *
     * @return string   Return description (if any) ...
     * @access protected
     */
    protected function _getAttributeColumnDefinition($code, $data)
    {
        $definition = parent::_getAttributeColumnDefinition($code, $data);

        if ($code === 'bronto_imported' && is_string($definition)) {
            return 'datetime NULL DEFAULT NULL';
        }

        return $definition;
    }

    /**
     * Remove entity attribute. Overwritten for flat entities support
     *
     * @param mixed $entityTypeId
     * @param mixed $code
     *
     * @return $this|Mage_Eav_Model_Entity_Setup
     */
    public function removeAttribute($entityTypeId, $code)
    {
        if (isset($this->_flatEntityTables[$entityTypeId]) &&
            $this->_flatTableExist($this->_flatEntityTables[$entityTypeId])
        ) {
            $this->_removeFlatAttribute($this->_flatEntityTables[$entityTypeId], $code);
            $this->_removeGridAttribute($this->_flatEntityTables[$entityTypeId], $code, $entityTypeId);
        } else {
            parent::removeAttribute($entityTypeId, $code);
        }

        return $this;
    }

    /**
     * Remove an attribute as separate column in the table
     * The sales setup class does not support it by default
     *
     * @param $table
     * @param $attribute
     *
     * @return $this
     */
    protected function _removeFlatAttribute($table, $attribute)
    {
        $this->getConnection()->dropColumn($this->getTable($table), $attribute);

        return $this;
    }

    /**
     * Remove attribute from grid
     *
     * @param $table
     * @param $attribute
     * @param $entityTypeId
     *
     * @return $this
     */
    protected function _removeGridAttribute($table, $attribute, $entityTypeId)
    {
        if (in_array($entityTypeId, $this->_flatEntitiesGrid)) {
            $this->getConnection()->dropColumn($this->getTable($table . '_grid'), $attribute);
        }

        return $this;
    }
}
