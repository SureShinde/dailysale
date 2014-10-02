<?php
    $installer = $this;
    $installer->startSetup();
    $installer->run("

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/campaign')} (
            `campaign_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campaign Id',
            `template_code` varchar(150) NOT NULL COMMENT 'Campaign Name',
            `track_opens` tinyint(1) DEFAULT NULL COMMENT 'Track Opens Emails',
            `track_clicks` tinyint(1) DEFAULT NULL COMMENT 'Track Click Emails',
            `google_analitics` tinyint(1) DEFAULT NULL COMMENT 'Use Google Analitycs',
            `google_source` varchar(80) DEFAULT NULL,
            `google_medium` varchar(80) DEFAULT NULL,
            `google_title` varchar(100) DEFAULT NULL COMMENT 'Google Analitycs Title',
            `template_text` text NOT NULL COMMENT 'Template Content',
            `template_styles` text COMMENT 'Templste Styles',
            `template_type` int(10) unsigned DEFAULT NULL COMMENT 'Template Type',
            `template_subject` varchar(200) NOT NULL COMMENT 'Template Subject',
            `template_sender_name` varchar(200) DEFAULT NULL COMMENT 'Template Sender Name',
            `template_sender_email` varchar(200) DEFAULT NULL COMMENT 'Template Sender Email',
            `added_at` timestamp NULL DEFAULT NULL COMMENT 'Date of Template Creation',
            `modified_at` timestamp NULL DEFAULT NULL COMMENT 'Date of Template Modification',
            `orig_template_code` varchar(200) DEFAULT NULL COMMENT 'Original Template Code',
            `orig_template_variables` text COMMENT 'Original Template Variables',
            `tm_segment` int(11) DEFAULT NULL,
            `tm_gateway` int(11) DEFAULT NULL,
          PRIMARY KEY (`campaign_id`)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='NewsletterBooster Email Templates' AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/store')} (
            `campaign_store_id` int(11) NOT NULL AUTO_INCREMENT,
            `campaign_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            PRIMARY KEY (`campaign_store_id`),
            KEY `campaign_id` (`campaign_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/gateway')} (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(45) NOT NULL,
          `status` tinyint(1) NOT NULL,
          `host` varchar(45) NOT NULL,
          `user` varchar(45) NOT NULL,
          `password` varchar(255) NOT NULL,
          `port` varchar(15) NOT NULL,
          `secure` varchar(5) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/queue')} (
            `queue_id` int(11) NOT NULL AUTO_INCREMENT,
            `campaign_id` int(11) NOT NULL,
            `queue_title` varchar(80) NOT NULL,
            `queue_status` tinyint(1) NOT NULL,
            `queue_start_at` timestamp NULL DEFAULT NULL,
            `queue_finish_at` timestamp NULL DEFAULT NULL,
            `campaign_serialize` text NOT NULL,
            `processed` int(11) NOT NULL DEFAULT '0',
            `errors` int(11) NOT NULL DEFAULT '0',
            `recipients` int(11) DEFAULT NULL,
            PRIMARY KEY (`queue_id`),
            KEY `tm_newsletterbooster_queue_campaign` (`campaign_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/send')} (
          `queue_send_id` int(11) NOT NULL AUTO_INCREMENT,
          `queue_id` int(11) NOT NULL,
          `customer_id` int(11) NOT NULL,
          `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`queue_send_id`),
          KEY `tm_newsletterbooster_queue_send_queue` (`queue_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/trackclick')} (
            `click_id` int(11) NOT NULL AUTO_INCREMENT,
            `queue_id` int(11) NOT NULL,
            `entity_id` int(11) NOT NULL,
            `create_at` datetime NOT NULL,
            `ip` varchar(20) DEFAULT NULL,
            `country_code` varchar(5) DEFAULT NULL,
            `country_name` varchar(45) DEFAULT NULL,
            `city` varchar(45) DEFAULT NULL,
            PRIMARY KEY (`click_id`),
            KEY `queue_id` (`queue_id`)
          ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/trackopen')} (
            `open_id` int(11) NOT NULL AUTO_INCREMENT,
            `queue_id` int(11) NOT NULL,
            `entity_id` int(11) NOT NULL,
            `create_at` datetime NOT NULL,
            PRIMARY KEY (`open_id`),
            KEY `queue_id` (`queue_id`)
          ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS `tm_newsletterbooster_unsubscribe` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `campaign_id` int(11) NOT NULL,
            `queue_id` int(11) NOT NULL,
            `entity_id` int(10) unsigned NOT NULL,
            `create_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `campaign_id` (`campaign_id`),
            KEY `entity_id` (`entity_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

        ALTER TABLE {$this->getTable('newsletterbooster/store')}
            ADD CONSTRAINT `tm_newsletterbooster_campaign_store_1`
                FOREIGN KEY (`campaign_id`)
                REFERENCES {$this->getTable('newsletterbooster/campaign')} (`campaign_id`)
                ON DELETE CASCADE ON UPDATE CASCADE;

        ALTER TABLE {$this->getTable('newsletterbooster/queue')}
            ADD CONSTRAINT `tm_newsletterbooster_queue_campaign`
            FOREIGN KEY (`campaign_id`)
            REFERENCES {$this->getTable('newsletterbooster/campaign')} (`campaign_id`)
            ON DELETE CASCADE ON UPDATE CASCADE;

        ALTER TABLE {$this->getTable('newsletterbooster/send')}
            ADD CONSTRAINT `tm_newsletterbooster_queue_send_1`
            FOREIGN KEY (`queue_id`)
            REFERENCES {$this->getTable('newsletterbooster/queue')} (`queue_id`)
            ON DELETE CASCADE ON UPDATE CASCADE;

        ALTER TABLE {$this->getTable('newsletterbooster/trackclick')}
            ADD CONSTRAINT `tm_newsletterbooster_queue_trackclick_1`
            FOREIGN KEY (`queue_id`)
            REFERENCES {$this->getTable('newsletterbooster/queue')} (`queue_id`)
            ON DELETE CASCADE ON UPDATE CASCADE;

        ALTER TABLE {$this->getTable('newsletterbooster/trackopen')}
            ADD CONSTRAINT `tm_newsletterbooster_queue_trackopen_1`
            FOREIGN KEY (`queue_id`)
            REFERENCES {$this->getTable('newsletterbooster/queue')} (`queue_id`)
            ON DELETE CASCADE ON UPDATE CASCADE;

        ALTER TABLE {$this->getTable('newsletterbooster/unsubscribe')}
            ADD CONSTRAINT `tm_newsletterbooster_campaign_unsubscribe_1`
            FOREIGN KEY (`campaign_id`)
            REFERENCES {$this->getTable('newsletterbooster/campaign')} (`campaign_id`)
            ON DELETE CASCADE ON UPDATE CASCADE;

    ");
    $installer->endSetup();
?>