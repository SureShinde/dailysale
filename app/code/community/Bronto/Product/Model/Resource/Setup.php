<?php

class Bronto_Product_Model_Resource_Setup extends Bronto_Common_Model_Resource_Abstract
{
    /**
     * @see parent
     */
    protected function _module()
    {
        return 'bronto_product';
    }

    /**
     * @see parent
     */
    protected function _tables()
    {
        return array(
        'recommendation' => "
        CREATE TABLE `{table}` (
          `entity_id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `content_type` varchar(30) NOT NULL DEFAULT 'api',
          `tag_id` varchar(50) NULL,
          `store_id` int(11) NOT NULL DEFAULT '1' COMMENT 'Store ID for Recommendation',
          `tag_content` text NULL,
          `number_of_items` smallint(2) NOT NULL DEFAULT 5,
          `primary_source` varchar(20) NOT NULL DEFAULT 'related',
          `secondary_source` varchar(20) NOT NULL DEFAULT 'bestseller',
          `fallback_source` varchar(20) NOT NULL DEFAULT '',
          `exclusion_source` varchar(20) NOT NULL DEFAULT 'custom',
          `manual_primary_source` varchar(255) NULL,
          `manual_secondary_source` varchar(255) NULL,
          `manual_fallback_source` varchar(255) NULL,
          `manual_exclusion_source` text NULL,
          PRIMARY KEY (`entity_id`),
          KEY `IDX_BRONTO_PRODUCT_NAME` (`name`),
          KEY `IDX_BRONTO_PRODUCT_STOREID` (`store_id`),
          KEY `IDX_BRONTO_PRODUCT_TYPE` (`content_type`),
          KEY `IDX_BRONTO_PRODUCT_PRIMARY` (`primary_source`),
          KEY `IDX_BRONTO_PRODUCT_SECONDARY` (`secondary_source`),
          KEY `IDX_BRONTO_PRODUCT_FALLBACK` (`fallback_source`),
          KEY `IDX_BRONTO_PRODUCT_EXCLUSION` (`exclusion_source`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Product Recommentation table';"
        );
    }

    /**
     * @see parent
     */
    protected function _updates()
    {
        return array(
            '1.0.1' => array(
                'recommendation' => array(
                    'sql' => array(
                        "ALTER TABLE {table} ADD COLUMN `exclusion_source` varchar(20) NOT NULL DEFAULT 'custom' AFTER `fallback_source`",
                        "ALTER TABLE {table} ADD COLUMN `manual_exclusion_source` text NULL AFTER `manual_fallback_source`",
                        "ALTER TABLE {table} ADD INDEX `IDX_BRONTO_PRODUCT_EXCLUSION` (`exclusion_source`)"
                    )
                )
            )
        );
    }
}
