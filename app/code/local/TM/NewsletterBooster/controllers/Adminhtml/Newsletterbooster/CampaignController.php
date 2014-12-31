<?php

class TM_NewsletterBooster_Adminhtml_Newsletterbooster_CampaignController extends Mage_Adminhtml_Controller_Action
{
    protected $_emailCount = 250;

    protected $_processTime = 20;

    public function indexAction()
    {
        $this->_title($this->__('Campaign'))->_title(
            Mage::helper('newsletterbooster')->__('Campaign')
        );

        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;

        }
        $this->loadLayout();
        $this->_setActiveMenu('templates_master/newsletterbooster/campaign');
        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('NewsletterBooster'),
            Mage::helper('newsletterbooster')->__('NewsletterBooster')
        );

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template')
        );
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_grid')->toHtml()
        );
    }

    public function subscriberAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_subs')->toHtml()
        );
    }

    public function unsubscribeAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_unsub')->toHtml()
        );
    }

    /**
     * New transactional email action
     *
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit transactioanl email action
     *
     */
    public function editAction()
    {
        $this->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('templates_master/newsletterbooster/campaign');
        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('Campaign'),
            Mage::helper('newsletterbooster')->__('Campaign'),
            $this->getUrl('*/*')
        );

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('Edit Campaign'),
                Mage::helper('newsletterbooster')->__('Edit Campaign')
            );

        } else {
            $this->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('New Campaign'),
                Mage::helper('newsletterbooster')->__('New Campaign')
            );
        }

        $this->_title($template->getId() ? $template->getTemplateCode() : $this->__('New Campaign'));

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_edit', 'template_edit')
                ->setEditMode((bool)$this->getRequest()->getParam('id'))
        );

        $this->renderLayout();
    }

    public function subsAction()
    {
        $this->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('templates_master/newsletterbooster/campaign');
        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('Subscribers'),
            Mage::helper('newsletterbooster')->__('Subscribers'),
            $this->getUrl('*/*')
        );

        if ($this->getRequest()->getParam('campaign_id')) {
            $this->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('View Subscribers'),
                Mage::helper('newsletterbooster')->__('View Subscribers')
            );

        }

        $this->_title($template->getId() ? $template->getTemplateCode() : $this->__('View Subscribers'));

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_subs', 'template_subs')
                ->setEditMode((bool)$this->getRequest()->getParam('campaign_id'))
        );

        $this->renderLayout();
    }

    public function unsubAction()
    {
        $this->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('templates_master/newsletterbooster/campaign');
        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('Unsubscribers'),
            Mage::helper('newsletterbooster')->__('Unsubscribers'),
            $this->getUrl('*/*')
        );

        if ($this->getRequest()->getParam('campaign_id')) {
            $this->_addBreadcrumb(
                Mage::helper('newsletterbooster')->__('Unsubscribeds'),
                Mage::helper('newsletterbooster')->__('Unsubscribeds')
            );

        }

        $this->_title($template->getId() ? $template->getTemplateCode() : $this->__('View Unsubscribeds'));

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_unsub', 'template_unsub')
                ->setEditMode((bool)$this->getRequest()->getParam('campaign_id'))
        );

        $this->renderLayout();
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        $id = $this->getRequest()->getParam('id');

        $template = $this->_initTemplate('id');
        if (!$template->getId() && $id) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterbooster')->__('This Email Campaign no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }

        try {
            if ($request->getParam('tm_segment')) {
                $segments = implode(',', $request->getParam('tm_segment'));
            } else {
                $segments = '';
            }

            $template->setTemplateSubject($request->getParam('template_subject'))
                ->setTemplateCode($request->getParam('template_code'))
                ->setDescription($request->getParam('description'))
                ->setTrackOpens($request->getParam('track_opens'))
                ->setTrackClicks($request->getParam('track_clicks'))
                ->setGoogleAnalitics($request->getParam('google_analitics'))
                ->setGoogleTitle($request->getParam('google_title'))
                ->setGoogleMedium($request->getParam('google_medium'))
                ->setGoogleSource($request->getParam('google_source'))
                ->setGoogleContent($request->getParam('google_content'))
                ->setSentGuest($request->getParam('sent_guest'))
                ->setTmSegment($segments)
                ->setTmGateway($request->getParam('tm_gateway'))
                ->setInFrontend($request->getParam('in_frontend'))
                ->setTemplateSenderEmail($request->getParam('template_sender_email'))
                ->setTemplateSenderName($request->getParam('template_sender_name'))
                ->setTemplateText($request->getParam('template_text'))
                ->setTemplateStyles($request->getParam('template_styles'))
                ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
                ->setOrigTemplateCode($request->getParam('orig_template_code'))
                ->setOrigTemplateVariables($request->getParam('orig_template_variables'));

            if (!$template->getCampaignId()) {
                //$type = constant(Mage::getConfig()->getModelClassName('core/email_template') . "::TYPE_HTML");
                $template->setTemplateType(TM_NewsletterBooster_Model_Campaign::TYPE_HTML);
                $template->setAddedAt(Mage::getSingleton('core/date')->gmtDate());
            }

            if($request->getParam('_change_type_flag')) {
                //$type = constant(Mage::getConfig()->getModelClassName('core/email_template') . "::TYPE_TEXT");
                $template->setTemplateType(TM_NewsletterBooster_Model_Campaign::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            $template->save();

            /* Save Campaign Store Ids */
            $storeModel = Mage::getModel('newsletterbooster/store');
            $stores = $request->getParam('stores');
            if (is_array($stores)) {
                $storeModel->deleteCampaignStoreIds($template->getCampaignId());
                foreach ($stores as $store) {
                    $storeModel->setId(null)
                        ->addData(array('campaign_id' => $template->getCampaignId()))
                        ->addData(array('store_id' => $store))
                        ->save();

                }
            }

            Mage::getSingleton('adminhtml/session')->setFormData(false);
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterbooster')->__('The campaign has been saved.')
            );
            if ($request->getParam('save_and_queue') == 1) {
                $queueUrl = Mage::helper("adminhtml")->getUrl(
                    "*/newsletterbooster_queue/edit",
                    array('campaign' => $template->getCampaignId())
                );
                $this->_redirectUrl($queueUrl);
            } else {
                $this->_redirect('*/*/edit',array('id'=>$template->getCampaignId()));
            }

        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->setData('email_template_form_data', $this->getRequest()->getParams());
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_forward('new');
        }

    }

    public function deleteAction() {

        $template = $this->_initTemplate('id');
        if($template->getCampaignId()) {
            try {
                $template->delete();
                 // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterbooster')->__('The email template has been deleted.')
                );
                // go to grid
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('newsletterbooster')->__('An error occurred while deleting email template data. Please review log and try again.')
                );
                Mage::logException($e);
                // save data in session
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }
        // display error message
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('newsletterbooster')->__('Unable to find a Email Template to delete.')
        );
        // go to grid
        $this->_redirect('*/*/');
    }

    public function previewAction()
    {
        $this->loadLayout('newsletterboosterPreview');
        $this->renderLayout();
    }

    /**
     * Set template data to retrieve it in template info form
     *
     */
    public function defaultTemplateAction()
    {
        $template = $this->_initTemplate('id');

        $templateCode = $this->getRequest()->getParam('code');

        $template->loadDefault($templateCode, $this->getRequest()->getParam('locale'));
        $template->setData('orig_template_code', $templateCode);
        $template->setData('template_variables', Zend_Json::encode($template->getVariablesOptionArray(true)));

        $templateBlock = $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_edit');
        $template->setData('orig_template_used_default_for', $templateBlock->getUsedDefaultForPaths(false));

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($template->getData()));
    }

    protected function _initTemplate($idFieldName = 'campaign_id')
    {
        $this->_title(Mage::helper('newsletterbooster')->__('NewsletterBooster'));

        $id = (int)$this->getRequest()->getParam($idFieldName);

        $model = Mage::getModel('newsletterbooster/campaign');
        if ($id > 0) {
            $model->load($id);
        }
        if (!Mage::registry('email_template')) {
            Mage::register('email_template', $model);
        }

        if (!Mage::registry('current_email_template')) {
            Mage::register('current_email_template', $model);
        }
        return $model;
    }

    public function sendCampaignAction()
    {
        $templateData = $this->getRequest()->getParams();

        try {
            $campaignModel = Mage::getModel('newsletterbooster/campaign');
            if (isset($templateData['campaign_id'])) {
                $campaignModel->load($templateData['campaign_id']);
                $campaignModel->sendTestMail($templateData['test_email']);
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterbooster')->__('Test email was successfully sened'));
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'processed' => 1,
                'completed' => true,
                'message'   => Mage::helper('newsletterbooster')->__(
                    'Test email was successfully sent'
                )
            )));
        } catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
}
