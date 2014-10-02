<?php
class TM_NewsletterBooster_Block_Unsubscribe extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('newsletterbooster/form/unsubscribe.phtml');
        Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('root')->setHeaderTitle(
            Mage::helper('newsletterbooster')->__('NewsletterBooster')
        );
    }
}
