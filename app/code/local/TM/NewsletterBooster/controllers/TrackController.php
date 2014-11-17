<?php
class TM_NewsletterBooster_TrackController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $imagePath = Mage::getBaseDir('media').DS.'newsletterbooster'.DS.'track.gif';
        $queueId = $this->getRequest()->getParam('queue');
        $customerId = $this->getRequest()->getParam('entity');
        $icon = new Varien_Image($imagePath);

        $this->getResponse()->setHeader('Content-Type', $icon->getMimeType());
        $this->getResponse()->setBody($icon->display());
        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($queueId);
        if ($queue->getId()) {
            $trackOpen = Mage::getModel('newsletterbooster/trackopen');
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date($startAt, $format)->getTimestamp();
            if (!$trackOpen->openExist($queueId, $customerId)) {
                $trackOpen->setId(null)
                    ->setQueueId($queueId)
                    ->setEntityId($customerId)
                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                $trackOpen->save();
            }
        }
        return $this;
    }

    public function clickAction()
    {
        $queueId = $this->getRequest()->getParam('queue');
        $customerId = $this->getRequest()->getParam('entity');
        $decodeUrl = $this->getRequest()->getParam('redirect');

        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($queueId);
        if ($queue->getId()) {
            $trackClick = Mage::getModel('newsletterbooster/trackclick');
            $ip = Mage::helper('core/http')->getRemoteAddr();
            $geoData = Mage::helper('newsletterbooster/geo')->getGeoData($ip);
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date($startAt, $format)->getTimestamp();
            if (!$trackClick->clickExist($queueId, $customerId)) {
                $trackClick->setId(null)
                    ->setQueueId($queueId)
                    ->setEntityId($customerId)
                    ->setIp($ip)
                    ->setCountryCode($geoData['country_code'])
                    ->setCountryName($geoData['country_name'])
                    ->setCity($geoData['city'])
                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                $trackClick->save();
            }

            if ($queue->getGoogleAnalitics()) {
                $redirect = base64_decode($decodeUrl);
                if (strstr($redirect, '?')) {
                    $redirectUrl = $redirect . '&utm_source='. $queue->getGoogleSource() 
                        .'&utm_medium=' . $queue->getGoogleMedium() 
                        .'&utm_campaign=' . $queue->getGoogleTitle()
                        .'&utm_content=' . $queue->getGoogleContent();
                } else {
                    $redirectUrl = $redirect . '?utm_source='. $queue->getGoogleSource() 
                        .'&utm_medium=' . $queue->getGoogleMedium() 
                        .'&utm_campaign=' . $queue->getGoogleTitle()
                        .'&utm_content=' . $queue->getGoogleContent();
                }
            } else {
                $redirectUrl = base64_decode($decodeUrl);
            }

            $this->_redirectUrl($redirectUrl);
        }
    }

    public function unsubscribeAction()
    {
        $this->loadLayout();
        $helper = Mage::helper('newsletterbooster');
        if (!$this->getRequest()->getParam('id')) {
            Mage::getSingleton('core/session')->addError($helper->__('Wrong param campaign for unsubscribe campaign'));
            $this->_redirectUrl(Mage::getBaseUrl());
            return false;
        }
        
        if (!$this->getRequest()->getParam('queue')) {
            Mage::getSingleton('core/session')->addError($helper->__('Wrong param queue for unsubscribe campaign'));
            $this->_redirectUrl(Mage::getBaseUrl());
            return false;
        }

        $this->renderLayout();

        return;
    }

    public function unsubscribepostAction()
    {
        if ($this->getRequest()->isPost()) {
            $campaignId = $this->getRequest()->getParam('campaign');
            if ($this->getRequest()->getParam('entity')) {
                $customerId = $this->getRequest()->getParam('entity');
            } else {
                $customerId = null;
            }
            
            $queueId = $this->getRequest()->getParam('queue');
            $email = $this->getRequest()->getParam('email');
            $unsubscribe = Mage::getModel('newsletterbooster/unsubscribe');

            $helper = Mage::helper('newsletterbooster');
            $errorUrl = Mage::getUrl(
                'newsletterbooster/track/unsubscribe',
                array('id' => $campaignId,'entity' => $customerId, 'queue' =>$queueId)
            );

            if (!$unsubscribe->customerExist($customerId, $email) && null !== $customerId) {
                Mage::getSingleton('core/session')->addError($helper->__('Wrong customer email'));
                $this->_redirectUrl($errorUrl);
                return false;
            }
            
            if (!$unsubscribe->unsubscribeExist($campaignId, $queueId, $customerId, $email)) {
                if (null === $customerId) {
                    $guest = 1;
                    $subscribeModel = Mage::getModel('newsletterbooster/subscriber');
                    $guestId = $subscribeModel->getSubscriberId($campaignId, $email);
                    
                    $subscribeModel->load($guestId[0]);
                    $firstName = $subscribeModel->getFirstname();
                    $lastName = $subscribeModel->getLastname();
                } else {
                    $customerModel = Mage::getModel('customer/customer');
                    $guest = 0;
                    $customerModel->load($customerId);
                    $firstName = $customerModel->getFirstname();
                    $lastName = $customerModel->getLastname();
                }
                
                $unsubscribe->setId(null)
                    ->setCampaignId($campaignId)
                    ->setEntityId($customerId)
                    ->setQueueId($queueId)
                    ->setEmail($email)
                    ->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setIsGuest($guest)
                    ->setCreateAt(Mage::getSingleton('core/date')->gmtDate());

                try {
                    $unsubscribe->save();
                    $subscribe = Mage::getModel('newsletterbooster/subscriber');
                    if (null === $customerId) {
                        $subscribe->deleteSubscribeRecord($campaignId, $email, $customerId);
                    }
                    /* Unsubscribing from general subscription */
                    try {
                        $gSubscriber = Mage::getModel('newsletter/subscriber')
                            ->loadByEmail($email)->unsubscribe();
                        if ($gSubscriber->getId()) {
                            $gSubscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)->save();
                        }
                    } catch (Exception $genUnsubExcp) {
                        Mage::getSingleton('core/session')->addError($helper->__('We had a problem unsubscribing your email address. Please email us at unsub@dailysale.com with your email address for a manual action.'));
                        $this->_redirectUrl(Mage::getBaseUrl());
                    }
                    
                    Mage::getSingleton('core/session')->addSuccess($helper->__('Unsubscribe complete saving'));
                    $this->_redirectUrl(Mage::getBaseUrl());
                    return false;
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError($helper->__('Wrong params for unsubscribe campaign'));
                    $this->_redirectUrl(Mage::getBaseUrl());
                    return false;
                }
            } else {
                Mage::getSingleton('core/session')->addNotice($helper->__('You are already unsubscribed from campaign'));
                $this->_redirectUrl(Mage::getBaseUrl());
                return false;
            }
        }
        Mage::getSingleton('core/session')->addError($helper->__('Wrong params for unsubscribe campaign.'));
        $this->_redirectUrl(Mage::getBaseUrl());
        return false;
    }

}