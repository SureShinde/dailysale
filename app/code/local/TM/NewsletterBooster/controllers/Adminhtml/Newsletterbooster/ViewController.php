<?php

class TM_NewsletterBooster_Adminhtml_Newsletterbooster_ViewController extends Mage_Adminhtml_Controller_Action
{
    protected $_productCount = 50;
    protected $_processTime = 20;

    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('templates_master/newsletterbooster/subscriber')
            ->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('Campaign'),
                Mage::helper('newsletterbooster')->__('Subscribers')
            );

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_view')
        );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    public function importGuestAction()
    {
        try {
            $timeStart = time();
            $subscribeModel = Mage::getResourceModel('newsletterbooster/subscriber');

            if ($this->getRequest()->getParam('clear_session')) {
                $obj = new Varien_Object();
                Mage::getSingleton('adminhtml/session')->setData('newsletterbooster_object', $obj);
                $obj->setQueryStep(0);
                $obj->setProcessed(0);
                $obj->setProductCount($this->_productCount);
            } else {
                $obj = Mage::getSingleton('adminhtml/session')->getData('newsletterbooster_object');
                $queryOffset = $obj->getQueryStep();
            }

            if ($obj->getReindexData()) {
                $result = $this->processRelations($timeStart);
                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                    'completed' => false,
                    'message'   => Mage::helper('newsletterbooster')->__(
                            '%d record(s) loaded', $obj->getProcessed()
                        )
                )));
            }
            $reindexData = $subscribeModel->getItemsToProcess($obj->getProductCount(), $obj->getQueryStep());

            $obj->addData(array(
                'reindex_data'      => $reindexData,
                'query_step'        => $obj->getQueryStep(),
                'order_offset'      => 0,
            ));

            if ($obj->getReindexData()) {

                $result = $this->processRelations($timeStart);
                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                    'completed' => false,
                    'message'   => Mage::helper('newsletterbooster')->__(
                            '%d record(s) loaded', $obj->getProcessed()
                        )
                )));
            }

            Mage::getSingleton('adminhtml/session')->unsetData('newsletterbooster_object');
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterbooster')->__(
                    '%d record(s) was successfully loaded', $obj->getProcessed()
                )
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'processed' => $obj->getProcessed(),
                'completed' => true,
                'message'   => Mage::helper('newsletterbooster')->__(
                        '%d record(s) loaded', $obj->getProcessed()
                    )
            )));
        } catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function processRelations($timeStart)
    {
        $obj = Mage::getSingleton('adminhtml/session')->getData('newsletterbooster_object');
        $reindexData = $obj->getReindexData();

        $subscribeModel = Mage::getResourceModel('newsletterbooster/subscriber');
        $subscribe = Mage::getModel('newsletterbooster/subscriber');
        $campaignId = $this->getRequest()->getParam('campaign');

        for ($i = $obj->getOrderOffset(); $i < count($reindexData); $i++){
            if (!$subscribe->subscribeExist($campaignId, null, $reindexData[$i]['subscriber_email'])) {
                $locale = Mage::app()->getLocale();
                $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
                $time = $locale->date(now(), $format)->getTimestamp();

                $subscribe->setId(null);
                $subscribe->setCampaignId($campaignId)
                    ->setEntityId(0)
                    ->setIsGuest(1)
                    ->setImported(1)
                    ->setCreateAt(Mage::getModel('core/date')->gmtDate(null, $time))
                    ->setEmail($reindexData[$i]['subscriber_email']);
                $subscribe->save();
            }

            $timeEnd = time();
            $obj->setProcessed($obj->getProcessed() + 1);
            $obj->setOrderOffset($i + 1);
            $timeRes = $timeEnd - $timeStart;
            if ($timeRes > $this->_processTime){
                return array(
                    'processed' => $i + 1,
                    'completed' => false
                );
            }
        }
        $obj->setQueryStep($obj->getQueryStep() + 1);
        $obj->setReindexData(array());
        return array(
            'processed' => $i + 1,
            'completed' => true
        );
    }
}