<?php
class TM_NewsletterBooster_CustomerController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
 
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
             
        // adding message in customer login page
        Mage::getSingleton('core/session')
                ->addSuccess(Mage::helper('newsletterbooster')->__('Please sign in or create a new account'));
        }
    }
    
    public function campaignAction()
    {                  
        $this->loadLayout();       
        $this->getLayout()->getBlock('head')->setTitle($this->__('NewsletterBooster Subscribed Campaign'));    
        $this->renderLayout();
    }
    
    public function subscribeAction()
    {
        if ($this->getRequest()->isPost()) {
            $helper = Mage::helper('newsletterbooster');
            $campaignIds = $this->getRequest()->getParam('campaigns');
            $entityId = $this->getRequest()->getParam('subscriber');
            $customer = Mage::getModel('customer/customer');
            $customer->load($entityId);
            $firstName = $customer->getData('firstname');
            $lastName = $customer->getData('lastname');
            $email = $customer->getData('email');
            $model = Mage::getModel('newsletterbooster/subscriber');
            $campaignModel = Mage::getModel('newsletterbooster/campaign');
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date($startAt, $format)->getTimestamp();
            $model->deleteCustomerSubscribe($entityId);
            foreach ($campaignIds as $campaignId) {
                try {
                    $subscriber = $model->getSubscriberId($campaignId, $email);
                    if (count($subscriber) > 0) {
                        $subscriberID = $subscriber[0];
                        $model->load($subscriberID);
                        $model->setEntityId($entityId)
                            ->setIsGuest(0);
                        $model->save();
                        continue;
                    }
                    $campaignModel->load($campaignId);
                    $model->setId(null);
                    $model->setEntityId($entityId)
                        ->setCampaignId($campaignId)
                        ->setEmail($email)
                        ->setFirstname($firstName)
                        ->setLastname($lastName)
                        ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                    $model->save();
                }
                catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('core/session')->addError($e->getMessage());
                    $this->_redirectUrl($redirectUrl);
                }
                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $helper->__('You are subscribed to %s campaign now',
                        $campaignModel->getTemplateCode()
                    )
                );
            }
        }
        $this->_redirectUrl(Mage::getUrl('newsletterbooster/customer/campaign'));
        return $this;
    }
}