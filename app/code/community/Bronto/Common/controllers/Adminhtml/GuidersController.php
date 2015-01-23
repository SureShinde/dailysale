<?php

class Bronto_Common_Adminhtml_GuidersController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Toggle whether or not to show the guide for this section again.
     */
    public function ToggleAction()
    {
        $section = $this->getRequest()->getParam('section', 'bronto_verify');
        $value   = $this->getRequest()->getParam('checkvalue', '0');

        // Get config object and scope details
        Mage::getModel('core/config')->saveConfig(
            $section . '/guide/display',
            $value
        );
    }
}