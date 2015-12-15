<?php

class Bronto_Common_Model_System_Config_Backend_Cron_Queue extends Bronto_Common_Model_System_Config_Backend_Cron
{
    protected $_cron_string_path = 'crontab/jobs/bronto_common_queue/schedule/cron_expr';
    protected $_cron_model_path = 'crontab/jobs/bronto_common_queue/run/model';
}
