<?php

/**
 * HTML button for start cron
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Startcron
    extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html ='<input type="button" style="width: 100%;" onclick="startCron(this)" value="Start" />';
        return $html;
    }
}