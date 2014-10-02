<?php
class TM_NewsletterBooster_Block_Customer extends Mage_Core_Block_Template
{
    public function getCustomerCampaigns()
    {
        $result = array();
        $customer = $this->getCustomer();
        if ($customer->getId()) {
            $subModel = Mage::getModel('newsletterbooster/subscriber');
            $result = $subModel->getCustomerSubCampaigns($customer->getId());
        }
        return $result;
    }
    
    public function getCampaigns()
    {
        $campaigns = Mage::getResourceModel('newsletterbooster/campaign_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('in_frontend', array(
                'eq' => 1
            ));

        return $campaigns;
    }
    
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }
}
