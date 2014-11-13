<?php
    $installer = $this;
    $installer->startSetup();
    $installer->run("
        CREATE TABLE {$this->getTable('segmentationsuite/segments')} (
          `segment_id` int(11) NOT NULL AUTO_INCREMENT,
          `segment_title` varchar(45) NOT NULL,
          `conditions_serialized` text NOT NULL,
          `segment_status` tinyint(1) NOT NULL,
          PRIMARY KEY (`segment_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE {$this->getTable('segmentationsuite/store')} (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `store_id` int(10) NOT NULL,
          `segment_id` int(10) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `tm_segmentationsuite_store` (`segment_id`),
          CONSTRAINT `tm_segmentationsuite_segment_store_id`
          FOREIGN KEY (`segment_id`)
              REFERENCES {$this->getTable('segmentationsuite/segments')} (`segment_id`)
              ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE {$this->getTable('segmentationsuite/index')} (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `segment_id` int(11) NOT NULL,
          `entity_id` int(10) unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `fk_tm_segmentationsuite_index_segment_idx` (`segment_id`),
          KEY `tm_segmentationsuite_customer` (`entity_id`),
          CONSTRAINT `fk_tm_segmentationsuite_index_segment`
              FOREIGN KEY (`segment_id`)
              REFERENCES {$this->getTable('segmentationsuite/segments')} (`segment_id`)
              ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `tm_segmentationsuite_index_customer`
              FOREIGN KEY (`entity_id`)
              REFERENCES {$this->getTable('customer/entity')} (`entity_id`)
              ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    ");
    $installer->endSetup();
?>