<?php
class TM_NewsletterBooster_SubscribeController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $helper = Mage::helper('newsletterbooster');
            $data = $this->getRequest()->getPost();
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $redirectUrl = $data['redirect'];
            if (!Mage::getStoreConfig('newsletterbooster/subscribe/guest')) {
                if (null === $customer->getId()) {
                    Mage::getSingleton('core/session')
                        ->addError($helper->__('For subscribe need login first'));
                    $this->_redirectUrl($data['redirect']);
                    return false;
                } else {
                    $entityId = $customer->getId();
                    $firstName = $data['firstname'];
                    $lastName = $data['lastname'];

                    if (strlen($data['firstname']) == 0) {
                        $firstName = $customer->getData('firstname');
                    }
                    if (strlen($data['lastname']) == 0) {
                        $lastName = $customer->getData('lastname');
                    }

                    $model = Mage::getModel('newsletterbooster/subscriber');
                    $campaignModel = Mage::getModel('newsletterbooster/campaign');
                    $campaignIds = $data['campaigns'];
                    $locale = Mage::app()->getLocale();
                    $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
                    $time = $locale->date(now(), $format)->getTimestamp();
                    foreach($campaignIds as $campaignId) {
                        $campaignModel->load($campaignId);
                        if ($model->subscribeExist($campaignId, $entityId, $data['email'])) {
                            Mage::getSingleton('core/session')
                                ->addError(
                                    $helper->__('You are subscribed to %s now', 
                                    $campaignModel->getTemplateCode()
                                )
                            );
                        } else {
                            try {
                                $model->setId(null);
                                $model->setEntityId($entityId)
                                    ->setCampaignId($campaignId)
                                    ->setEmail($data['email'])
                                    ->setFirstname($firstName)
                                    ->setLastname($lastName)
                                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                                $model->save();
                                $this->sendSubscribeSuccessEmail($model->getData());
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
                }
            } else {
                //customer login 
                if ($customer->getId()) {
                    $entityId = $customer->getId();
                    $firstName = $data['firstname'];
                    $lastName = $data['lastname'];

                    if (strlen($data['firstname']) == 0) {
                        $firstName = $customer->getData('firstname');
                    }
                    if (strlen($data['lastname']) == 0) {
                        $lastName = $customer->getData('lastname');
                    }

                    $model = Mage::getModel('newsletterbooster/subscriber');
                    $campaignModel = Mage::getModel('newsletterbooster/campaign');
                    $campaignIds = $data['campaigns'];
                    $locale = Mage::app()->getLocale();
                    $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
                    $time = $locale->date(now(), $format)->getTimestamp();
                    foreach($campaignIds as $campaignId) {
                        $campaignModel->load($campaignId);
                        if ($model->subscribeExist($campaignId, $entityId, $data['email'])) {
                            Mage::getSingleton('core/session')
                                ->addError(
                                    $helper->__('You are subscribed to %s now', 
                                    $campaignModel->getTemplateCode()
                                )
                            );
                        } else {
                            try {
                                $model->setId(null);
                                $model->setEntityId($entityId)
                                    ->setCampaignId($campaignId)
                                    ->setEmail($data['email'])
                                    ->setFirstname($firstName)
                                    ->setLastname($lastName)
                                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                                $model->save();

                                $this->sendSubscribeSuccessEmail($model->getData());
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
                } else {
                    //guest subscribe
                    $entityId = null;
                    $firstName = $data['firstname'];
                    $lastName = $data['lastname'];
                    $store = Mage::app()->getStore()->getStoreId();
                    $model = Mage::getModel('newsletterbooster/subscriber');
                    $campaignModel = Mage::getModel('newsletterbooster/campaign');
                    $campaignIds = $data['campaigns'];
                    $locale = Mage::app()->getLocale();
                    $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
                    $time = $locale->date(now(), $format)->getTimestamp();
                    foreach($campaignIds as $campaignId) {
                        $campaignModel->load($campaignId);
                        if ($model->subscribeExist($campaignId, $entityId, $data['email'])) {
                            Mage::getSingleton('core/session')
                                ->addError(
                                    $helper->__('You are subscribed to %s now', 
                                    $campaignModel->getTemplateCode()
                                )
                            );
                        } else {
                            try {
                                $model->setId(null);
                                $model->setEntityId($entityId)
                                    ->setCampaignId($campaignId)
                                    ->setEmail($data['email'])
                                    ->setFirstname($firstName)
                                    ->setLastname($lastName)
                                    ->setIsGuest(1)
                                    ->setStore($store)
                                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                                $model->save();

                                $this->sendSubscribeSuccessEmail($model->getData());
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
                }
            }
        }
        $this->_redirectUrl($redirectUrl);
        return $this;
    }

    public function sendSubscribeSuccessEmail($subscriberData = array())
    {
        if (!Mage::getStoreConfig('newsletterbooster/subscribe/mail')) {
            return false;
        }

        $emailTemplate  = Mage::getModel('core/email_template')
            ->loadDefault('newsletterbooster_subscribe_success');

        $emailTemplateVariables = array();
        $emailTemplateVariables['store'] =  Mage::getModel('core/store')->load($subscriberData['store']);
        $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
        $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        $emailTemplate->send($subscriberData['email'], $subscriberData['firstname']. ' ' .$subscriberData['lastname'] , $emailTemplateVariables);
    }
}