<?php

class Bronto_Product_Model_System_Config_Cron extends Bronto_Common_Model_System_Config_Backend_Cron
{
    protected $_cron_string_path = 'crontab/jobs/bronto_product_parse_tag/schedule/cron_expr';
    protected $_cron_model_path = 'crontab/jobs/bronto_product_parse_tag/run/model';
}
