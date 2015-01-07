<?php

class Bronto_Common_Model_System_Config_Backend_Cron_Error extends Bronto_Common_Model_System_Config_Backend_Cron
{
    protected $_cron_string_path = 'crontab/jobs/bronto_common_errors/schedule/cron_expr';
    protected $_cron_model_path  = 'crontab/jobs/bronto_common_errors/run/model';
}
