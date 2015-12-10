<?php

class Bronto_Common_Block_Adminhtml_Widget_Button_Debug extends Bronto_Common_Block_Adminhtml_Widget_Button_Abstract
{

    /**
     * Sets up the JS action for gathering debug information
     *
     * @see parent
     */
    protected function _setUp()
    {
        $this->setLabel('Generate Debug Information');
        $this->setOnClick('collectDebugInformation(); return false;');
    }
}
