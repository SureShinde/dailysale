<?php

class TM_NewsletterBooster_Block_Form_Unsubscribe extends Mage_Directory_Block_Data
{
    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('head')->setTitle(
            Mage::helper('newsletterbooster')->__('Unsubscribe Campaign')
        );
        return parent::_prepareLayout();
    }

    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getPostSaveActionUrl()
    {
        return $this->helper('newsletterbooster')->getUnsubscribeCampaignUrl();
    }
}
