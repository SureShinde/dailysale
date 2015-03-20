<?php

class Bronto_Customer_Model_Resource_Customer extends Mage_Customer_Model_Resource_Customer
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieve customer entity default attributes
     *
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return array(
            'entity_type_id',
            'attribute_set_id',
            'created_at',
            'updated_at',
            'increment_id',
            'store_id',
            'website_id',
            'is_active' // This should be in here by default, but it isn't
        );
    }
}
