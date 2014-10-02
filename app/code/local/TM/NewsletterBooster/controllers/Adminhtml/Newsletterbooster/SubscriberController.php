<?php

class TM_NewsletterBooster_Adminhtml_Newsletterbooster_SubscriberController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('templates_master/newsletterbooster/subscriber')
            ->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('Campaign'),
                Mage::helper('newsletterbooster')->__('Subscribers')
            );

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_subscriber')
        );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id', 0);

        $gateway = Mage::getModel('newsletterbooster/gateway')->load($id);
        $gatewayId = $gateway->getId();

        if (!$gatewayId && 0 !== $id) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterbooster')->__('Gateway does not exist')
            );
            $this->_redirect('*/*/');
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $gateway->setData($data);
        }

        Mage::register('newsletterbooster_gateway', $gateway);

        $this->loadLayout();
        $this->_setActiveMenu('templates_master/newsletterbooster/gateway');


        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent(
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_gateway_edit'
            )
        );
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_redirect('*/*/edit');
    }

    public function testAction()
    {
        $data = $this->getRequest()->getPost();
        // Create transport

        try  {
            $gateway = Mage::getModel('newsletterbooster/gateway');

            if (empty($data['id'])) {
                unset($data['id']);
            }
            $gateway->setData($data);

            $gateway->save();

            //success
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterbooster')->__('Email Gateway was successfully saved')
            );
            Mage::getSingleton('adminhtml/session')->setFormData(false);

            //test geteway connection
            $configMail = array(
                'auth' => 'login',
                'username' => $data['user'],
                'password' => $data['password'],
                'port' => $data['port']
            );
            if ($data['secure']) {
                $configMail['secure'] = $data['secure'];
            }

            $transport = new Zend_Mail_Transport_Smtp($data['host'], $configMail) ;
            try {
                $protocol = new Zend_Mail_Protocol_Smtp($data['host'], $data['port'], $configMail);
                if ($protocol->connect()) {
                    $protocol->quit();
                    $protocol->disconnect();
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterbooster')->__(
                    'Connection with mail server was succesfully established'
                    )
                    );
                    if ($gateway->getId()) {
                        $this->_redirect('*/*/edit', array('id' => $gateway->getId()));
                    } else {
                        Mage::getSingleton('adminhtml/session')
                        ->setFormData($this->getRequest()->getParams());
                        //Mage::register('newsletterbooster_gateway', $this->getRequest()->getPost());
                        $this->_redirect('*/*/edit');
                    }
                    return;
                }
            } catch (Zend_Mail_Protocol_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                $e->getMessage()
                );
                if (isset($data['id'])) {
                    $this->_redirect('*/*/edit', array('id' => $data['id']));
                } else {
                    Mage::getSingleton('adminhtml/session')
                    ->setFormData($data);
                    //Mage::register('newsletterbooster_gateway', $this->getRequest()->getPost());
                    $this->_redirect('*/*/new');
                }
                return;
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            return;
        }
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();

        if (!$data) {
             Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterbooster')->__('Unable to find item to save')
            );
            $this->_redirect('*/*/');
        }

        try  {
            $gateway = Mage::getModel('newsletterbooster/gateway');

            if (empty($data['id'])) {
                unset($data['id']);
            }
            $gateway->setData($data);

            $gateway->save();

            //success
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterbooster')->__('Email Gateway was successfully saved')
            );
            Mage::getSingleton('adminhtml/session')->setFormData(false);


            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', array('id' => $gateway->getId()));
                return;
            }
            $this->_redirect('*/*/');
            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            return;
        }
    }

    public function deleteAction()
    {
        $gatewayId = $this->getRequest()->getParam('id');
        if( 0 < $gatewayId) {
            try {
                $gateway = Mage::getModel('newsletterbooster/gateway');

                $gateway->setId($gatewayId)->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Email Gateway was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $gatewayId));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $gatewayIds = $this->getRequest()->getParam('newsletterbooster');
        if(!is_array($gatewayIds)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($gatewayIds as $gatewayId) {
                    $gateway = Mage::getModel('newsletterbooster/gateway')->load($gatewayId);
                    $gateway->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                    'Total of %d record(s) were successfully deleted', count($gatewayIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction()
    {
        $fileName   = 'newsletterbooster_gateway.csv';
        $content    = $this->getLayout()->createBlock('newsletterbooster/adminhtml_gateway_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'newsletterbooster_gateway.xml';
        $content    = $this->getLayout()->createBlock('newsletterbooster/adminhtml_gateway_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse(
        $fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}