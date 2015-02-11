<?php

class TM_NewsletterBooster_TrackController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
        $imagePath = Mage::getBaseDir('media') . DS . 'newsletterbooster' . DS . 'track.gif';
        $queueId = $this->getRequest()->getParam('queue');
        $customerId = $this->getRequest()->getParam('entity');
        $icon = new Varien_Image($imagePath);

        $this->getResponse()->setHeader('Content-Type', $icon->getMimeType());
        $this->getResponse()->setBody($icon->display());
        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($queueId);
        if($queue->getId()){
            $trackOpen = Mage::getModel('newsletterbooster/trackopen');
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date(null, $format)->getTimestamp();
            if(!$trackOpen->openExist($queueId, $customerId)){
                $trackOpen->setId(null)
                    ->setQueueId($queueId)
                    ->setEntityId($customerId)
                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time));
                $trackOpen->save();
            }
        }
        return $this;
    }

    public function clickAction(){
        $queueId = $this->getRequest()->getParam('queue');
        $customerId = $this->getRequest()->getParam('entity');
        $decodeUrl = $this->getRequest()->getParam('redirect');

        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($queueId);
        if($queue->getId()){
            $trackClick = Mage::getModel('newsletterbooster/trackclick');
            $ip = Mage::helper('core/http')->getRemoteAddr();
            $geoData = Mage::helper('newsletterbooster/geo')->getGeoData($ip);
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date(null, $format)->getTimestamp();
            if(!$trackClick->clickExist($queueId, $customerId)){
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

            if($queue->getGoogleAnalitics()){
                $redirect = base64_decode($decodeUrl);
                if(strstr($redirect, '?')){
                    $redirectUrl = $redirect . '&utm_source=' . $queue->getGoogleSource()
                        . '&utm_medium=' . $queue->getGoogleMedium()
                        . '&utm_campaign=' . $queue->getGoogleTitle()
                        . '&utm_content=' . $queue->getGoogleContent();
                } else{
                    $redirectUrl = $redirect . '?utm_source=' . $queue->getGoogleSource()
                        . '&utm_medium=' . $queue->getGoogleMedium()
                        . '&utm_campaign=' . $queue->getGoogleTitle()
                        . '&utm_content=' . $queue->getGoogleContent();
                }
            } else{
                $redirectUrl = base64_decode($decodeUrl);
            }

            $this->_redirectUrl($redirectUrl);
        }
    }

    public function unsubscribeAction(){
        $this->loadLayout();
        $helper = Mage::helper('newsletterbooster');
        if(!$this->getRequest()->getParam('id')){
            Mage::getSingleton('core/session')->addError($helper->__('Wrong param campaign for unsubscribe campaign'));
            $this->_redirectUrl(Mage::getBaseUrl());
            return false;
        }

        if(!$this->getRequest()->getParam('queue')){
            Mage::getSingleton('core/session')->addError($helper->__('Wrong param queue for unsubscribe campaign'));
            $this->_redirectUrl(Mage::getBaseUrl());
            return false;
        }

        $this->renderLayout();
    }

    public function unsubscribepostAction(){
        $helper = Mage::helper('newsletterbooster');

        if($this->getRequest()->isPost() && $this->getRequest()->getParam('email')){
            $email = $this->getRequest()->getParam('email');
            $queueId = (int)$this->getRequest()->getParam('queue');
            $campaignId = (int)$this->getRequest()->getParam('campaign');
            $customerId = ($this->getRequest()->getParam('entity')) ? (int)$this->getRequest()->getParam('entity') : 0;

            $subscribeModel = Mage::getModel('newsletterbooster/subscriber');
            $unSubscribeModel = Mage::getModel('newsletterbooster/unsubscribe');

            if(!$unSubscribeModel->unsubscribeExist($campaignId, $queueId, $customerId, $email)){
                $unSubscriber = $this->_getUnsibcriber($customerId, $campaignId, $email, $subscribeModel);

                try{
                    if($unSubscriber->getFirstName() || $unSubscriber->getLastName()){
                        $unSubscribeModel->setId(null)
                            ->setCampaignId($campaignId)
                            ->setEntityId($customerId)
                            ->setQueueId($queueId)
                            ->setEmail($unSubscriber->getEmail())
                            ->setFirstname($unSubscriber->getFirstName())
                            ->setLastname($unSubscriber->getLastName())
                            ->setIsGuest($unSubscriber->getIsGuest())
                            ->setCreateAt(Mage::getSingleton('core/date')->gmtDate())
                            ->save();
                    }

                    if(!$customerId){
                        $subscribeModel->deleteSubscribeRecord($campaignId, $email, $customerId);
                    }
                } catch(Exception $e){
                    Mage::logException($e);
                    Mage::getSingleton('core/session')->addError($helper->__('Wrong params for unsubscribe campaign'));
                    $this->_redirectUrl(Mage::getBaseUrl());
                    return false;
                }
            } else{
                Mage::getSingleton('core/session')->addNotice($helper->__('You are already unsubscribed from campaign'));
                $this->_redirectUrl(Mage::getBaseUrl());
                return false;
            }

            try{
                $gSubscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                if($gSubscriber->getId()){
                    $gSubscriber->unsubscribe();
                    Mage::getSingleton('core/session')->addSuccess($helper->__('Unsubscribed complete.'));
                } else{
                    Mage::getSingleton('core/session')->addError($helper->__('Email %s has not been found.', $email));
                    $this->_redirectUrl($this->_getRefererUrl());
                    return false;
                }
            } catch(Exception $e){
                Mage::logException($e);
                Mage::getSingleton('core/session')->addError($helper->__('We had a problem unsubscribing your email address. Please email us at unsub@dailysale.com with your email address for a manual action.'));
                $this->_redirectUrl(Mage::getBaseUrl());
                return false;
            }

        } else{
            Mage::getSingleton('core/session')->addError($helper->__('Please enter email to unsubscribe.'));
            $this->_redirectUrl($this->_getRefererUrl());
            return false;
        }

        $this->_redirectUrl(Mage::getBaseUrl());
    }

    /**
     * Retrieve unSubscriber object
     *
     * @param $customerId
     * @param $campaignId
     * @param $email
     * @param $subscribeModel
     * @return Varien_Object
     */
    protected function _getUnsibcriber($customerId, $campaignId, $email, $subscribeModel){
        $unSubscriber = new Varien_Object(array('email' => $email));
        if(!$customerId){
            $unSubscriber->setIsGuest(1);
            $guestId = $subscribeModel->getSubscriberId($campaignId, $email);
            if(isset($guestId[0])){
                $subscribeModel->load($guestId[0]);
                $unSubscriber->setFirstName($subscribeModel->getFirstname());
                $unSubscriber->setLastName($subscribeModel->getLastname());
            }
        } else{
            $unSubscriber->setIsGuest(0);
            $customerModel = Mage::getModel('customer/customer')->load($customerId);
            $unSubscriber->setFirstName($customerModel->getFirstname());
            $unSubscriber->setLastName($customerModel->getLastname());
        }

        return $unSubscriber;
    }
}