<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
    DROP TABLE IF EXISTS `{$this->getTable('bronto_newsletter_queue')}`;

    CREATE TABLE `{$this->getTable('bronto_newsletter_queue')}` (
      `queue_id` int(11) NOT NULL AUTO_INCREMENT,
      `subscriber_id` int(11) NOT NULL,
      `store` tinyint(4) NOT NULL,
      `status` varchar(32) CHARACTER SET utf8 NOT NULL,
      `message_preference` varchar(16) CHARACTER SET utf8 NOT NULL,
      `source` varchar(16) CHARACTER SET utf8 NOT NULL,
      `imported` tinyint(4) NOT NULL DEFAULT '0',
      `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
      `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At',
      `subscriber_email` varchar(255) CHARACTER SET utf8 NOT NULL,
      `bronto_suppressed` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`subscriber_id`,`store`),
      KEY `IDX_BRONTO_NEWSLETTER_QUEUE_QUEUE_ID` (`queue_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");

} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Newsletter Queue Table: ' . $e->getMessage());
}

try {
    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_newsletter_queue')}`
        (
          SELECT
            NULL,
            `newsletter`.`subscriber_id`,
            `newsletter`.`store_id`,
            IF(`newsletter`.`subscriber_status` = 1, 'active', IF(`newsletter`.`subscriber_status` = 2, 'transactional', 'unsub')),
            'html',
            'api',
            0,
            `newsletter`.`change_status_at`,
            `newsletter`.`change_status_at`,
            `newsletter`.`subscriber_email`,
            null
          FROM `{$this->getTable('newsletter_subscriber')}` `newsletter`
          WHERE NOT EXISTS(
            SELECT 1 FROM `{$this->getTable('bronto_newsletter_queue')}` `queue` WHERE
                `queue`.`subscriber_id`=`newsletter`.`subscriber_id` OR
                `queue`.`subscriber_email`=`newsletter`.`subscriber_email`
        ));
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Populating Newsletter Queue Table: ' . $e->getMessage());
}

$installer->endSetup();
