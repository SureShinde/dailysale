<?php

class Bronto_Common_Block_Adminhtml_Widget_Button_Download extends Bronto_Common_Block_Adminhtml_Widget_Button_Abstract
{

    /**
     * @see parent
     */
    protected function _setUp()
    {
        $this->setLabel('Create Log Archive');
        $this->setOnClick('createLogArchive(); return false;');
    }
}
