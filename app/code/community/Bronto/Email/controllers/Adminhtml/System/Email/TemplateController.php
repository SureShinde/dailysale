<?php

require_once 'Mage/Adminhtml/controllers/System/Email/TemplateController.php';

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Adminhtml_System_Email_TemplateController extends Mage_Adminhtml_System_Email_TemplateController
{

    protected $_returnableActions = array('save', 'delete');

    /**
     * Main Grid view for Transactional Email Templates
     * Overwritten to show Bronto Templates
     *
     * @return null
     */
    public function indexAction()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::indexAction();
        }

        $this->_title($this->__('System'))->_title($this->__('Transactional Emails'));
        Mage::getSingleton('adminhtml/session')->setPostRedirect('*/*/');

        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('brontoGrid');

            return false;
        }

        $this->loadLayout();
        $this->_setActiveMenu('system/email_template');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Bronto Transactional Emails'), Mage::helper('adminhtml')->__('Bronto Transactional Emails'));

        $this->_addContent($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_template', 'template'));
        $this->renderLayout();

        return $this;
    }

    /**
     * Main Grid view for Importing Transactional Email Templates into Bronto
     *
     * @return null
     */
    public function importAction()
    {
        $this->_title($this->__('System'))->_title($this->__('Import Transactional Emails'));
        Mage::getSingleton('adminhtml/session')->setPostRedirect('*/*/import');

        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');

            return;
        }

        $this->loadLayout();
        $this->_setActiveMenu('system/email_template');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Import Transactional Emails'), Mage::helper('adminhtml')->__('Import Transactional Emails'));

        $this->_addContent($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_import', 'import'));
        $this->renderLayout();
    }

    /**
     * Override Ajax grid for import to show custom grid for Magento Templates
     *
     * @return $this|void
     */
    public function gridAction()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::gridAction();
        }

        $this->getResponse()->setBody($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_import_grid')->toHtml());

        return $this;
    }

    /**
     * Override Ajax grid for index to show Bronto Templates
     *
     * @return $this|void
     */
    public function brontoGridAction()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::gridAction();
        }

        $this->getResponse()->setBody($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_template_grid')->toHtml());

        return $this;
    }

    /**
     * Create drop-down of templates
     */
    public function ajaxlistAction()
    {
        $template = $this->_initTemplate();
        $request  = $this->getRequest();
        $filter   = array();
        $storeId  = $request->getParam('id', null);
        $sendType = $request->getParam('type', false);
        if ('transactional' == $sendType) {
            $filter = array('transactional_approval' => 'accepted');
        }

        $messages = Mage::helper('bronto_email/message')->getMessagesOptionsArray($storeId, null, $filter, true);
        foreach ($messages as $message) {
            if ($message['value'] == $template->getBrontoMessageId()) {
                echo sprintf('<option value="%s" selected="selected">%s</option>', $message['value'], $message['label']);
            } else {
                echo sprintf('<option value="%s">%s</option>', $message['value'], $message['label']);
            }
        }
    }

    /**
     * Populate 'Original Template Text' field with Bronto Message Content on template change
     *
     * @access public
     */
    public function ajaxtemplateAction()
    {
        $templateId = $this->getRequest()->getParam('template_id', false);

        if ($templateId) {
            $template = Mage::getModel('bronto_email/template')->loadDefault($templateId);

            $templateContent = trim($template->getTemplateText());
            // echo Template Content
            echo $templateContent;
        }

        echo '';
    }

    /**
     * Determines if this action is a returnable action
     *
     * @return boolean
     */
    protected function _isReturnableAction()
    {
        return in_array($this->getRequest()->getActionName(), $this->_returnableActions);
    }

    /**
     * Sends the user back to the Post-Redirect location, if the action is returnable
     */
    protected function _postReturn()
    {
        $session = Mage::getSingleton('adminhtml/session');
        if ($this->_isReturnableAction() && $session->hasPostRedirect()) {
            $this->_redirect($session->getPostRedirect());
        }
    }

    /**
     * Override to route back to Post-Redirect location
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->_postReturn();
    }

    /**
     * Override Save Action for Bronto Templates
     *
     * @return $this|void
     */
    public function brontoSaveAction()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::saveAction();
        }

        $request  = $this->getRequest();
        $id       = $this->getRequest()->getParam('id');
        $template = $this->_initTemplate('id');

        if (!$template->getId() && $id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('This Email template no longer exists.'));
            $this->_redirect('*/*/');

            return $this;
        }

        try {
            // Get Send Type
            $sendType = $request->getParam('template_send_type');

            // Get Store ID
            $storeId = (Mage::app()->isSingleStoreMode()) ? Mage::app()->getDefaultStoreView()->getId() : $request->getParam('store_id');

            // Core Template Details
            $templateText    = ($sendType == 'magento') ? $request->getParam('template_text') : $request->getParam('template_text_hidden');
            $templateSubject = ($sendType == 'magento') ? $request->getParam('template_subject') : $request->getParam('template_subject_hidden');
            $templateStyles  = ($sendType == 'magento') ? $request->getParam('template_styles') : $request->getParam('template_styles_hidden');

            // Add Template Settings
            $template->setTemplateCode($request->getParam('template_code'))
                ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
                ->setTemplateText($templateText)
                ->setTemplateSubject($templateSubject)
                ->setTemplateStyles($templateStyles)
                ->setOrigTemplateCode($request->getParam('orig_template_code'))
                ->setOrigTemplateVariables($request->getParam('orig_template_variables'));

            // Handle Template Type Settings
            if (!$template->getId()) {
                $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML);
            }
            if ($request->getParam('_change_type_flag')) {
                $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            // Bronto Template Details
            $brontoMessageId   = ($sendType == 'magento') ? $request->getParam('bronto_message_id_hidden') : $request->getParam('bronto_message_id');
            $brontoMessageName = $this->_getMessageName($brontoMessageId);
            $origTemplateText  = ($sendType == 'magento') ? $request->getParam('template_text') : $request->getParam('orig_template_text', null);
            // If Original Template Text value is empty, pull from the hidden field that should have a value
            if ('' == $origTemplateText || is_null($origTemplateText)) {
                $origTemplateText = $request->getParam('template_text_hidden');
            }

            // If Bronto Message ID == '_new_' the importTemplate function will create the message
            //  in Bronto and add the details to the template entry
            if ('_new_' == $brontoMessageId && 'magento' != $sendType) {
                $template->save();

                $importModel = Mage::getModel('bronto_email/template_import');
                $importModel->importTemplate($template->getId(), $storeId);
                $brontoTemplate = Mage::getModel('bronto_email/message')
                    ->load($template->getId());
            } else {
                // Get Bronto Template and Add Template Settings
                $brontoTemplate = Mage::getModel('bronto_email/message')
                    ->setStoreId($storeId)
                    ->setBrontoMessageId($brontoMessageId)
                    ->setBrontoMessageName($brontoMessageName)
                    ->setOrigTemplateText($origTemplateText);
            }

            // Only set if the module is enabled, essentially
            if ($request->has('product_recommendation')) {
                $rec = $request->getParam('product_recommendation', null);
                $brontoTemplate->setProductRecommendation($rec);
            }

            if ($request->has('send_flags')) {
                $sendFlags = $request->getParam('send_flags', null);
                $brontoTemplate->setSendFlags($sendFlags);
            }

            $salesRule = $request->getParam('sales_rule', null);
            $brontoTemplate->setSalesRule($salesRule);
            $brontoTemplate->setTemplateSendType($sendType);

            // If Template doesn't exist or added_at is not set, set it
            if (!$template->getId() || !$template->getAddedAt()) {
                $template->setAddedAt(Mage::getSingleton('core/date')->gmtDate());
            }

            // Save Template
            $template->save();

            // Set Bronto Template ID to match newly saved Template and then save
            if ($brontoTemplate) {
                $brontoTemplate->setId($template->getId());
                $brontoTemplate->save();
            }

            Mage::getSingleton('adminhtml/session')->setFormData(false);
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The email template has been saved.'));
            $this->_redirect('*/*');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->setData('email_template_form_data', $this->getRequest()->getParams());
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_forward('brontoNew');
        }

        return $this;
    }

    /**
     * Get Message Name from Message ID
     *
     * @param string|int $messageId
     *
     * @return string
     */
    private function _getMessageName($messageId)
    {
        $messages = Mage::helper('bronto_common/message')->getAllMessageOptions();
        foreach ($messages as $message) {
            if ($message['value'] == $messageId) {
                return $message['label'];
            }
        }

        return false;
    }

    /**
     * Edit Default Templates
     *
     * @return $this
     */
    public function importEditAction()
    {
        $this->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('system/email_template');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Transactional Emails'), Mage::helper('adminhtml')->__('Transactional Emails'), $this->getUrl('*/*'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Edit Template'), Mage::helper('adminhtml')->__('Edit System Template'));
        } else {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('New Template'), Mage::helper('adminhtml')->__('New System Template'));
        }

        $this->_title($template->getId() ? $template->getTemplateCode() : $this->__('New Template'));

        $this->_addContent($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_import_edit', 'template_edit')
            ->setEditMode((bool)$this->getRequest()->getParam('id')));
        $this->renderLayout();

        return $this;
    }

    /**
     * Edit transactional email action
     */
    public function brontoEditAction()
    {
        $this->_forward('brontonew');
    }

    /**
     * Create transactional email action
     */
    public function brontoNewAction()
    {
        $this->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('system/email_template');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Transactional Emails'), Mage::helper('adminhtml')->__('Transactional Emails'), $this->getUrl('*/*'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Edit Template'), Mage::helper('adminhtml')->__('Edit System Template'));
        } else {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('New Template'), Mage::helper('adminhtml')->__('New System Template'));
        }

        $this->_title($template->getId() ? $template->getTemplateCode() : $this->__('New Template'));

        $this->_addContent($this->getLayout()->createBlock('bronto_email/adminhtml_system_email_template_edit', 'template_edit')
            ->setEditMode((bool)$this->getRequest()->getParam('id')));
        $this->renderLayout();
    }

    /**
     * Set template data to retrieve it in template info form
     */
    public function defaultTemplateAction()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::defaultTemplateAction();
        }

        $template     = $this->_initTemplate('id');
        $templateCode = $this->getRequest()->getParam('code');

        $template->loadDefault($templateCode, $this->getRequest()->getParam('locale'));
        $template->setData('orig_template_code', $templateCode);
        $template->setData('template_variables', Zend_Json::encode($template->getVariablesOptionArray(true)));

        $templateBlock = $this->getLayout()->createBlock('adminhtml/system_email_template_edit');
        $template->setData('orig_template_used_default_for', $templateBlock->getUsedDefaultForPaths(false));

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($template->getData()));

        return $this;
    }

    /**
     * Mass Action for Import
     */
    public function massImportAction()
    {
        $templateIds = $this->getRequest()->getParam('template_id', array());
        $storeId     = $this->getRequest()->getParam('store_id', null);
        $imported    = 0;

        // If single ID, set as array
        if (is_numeric($templateIds)) {
            $templateIds = array($templateIds);
        }

        // Begin Processing Templates
        if (count($templateIds) > 0) {
            $importModel = Mage::getModel('bronto_email/template_import');
            foreach ($templateIds as $templateId) {
                try {
                    if ($importModel->importTemplate($templateId, $storeId)) {
                        $imported++;
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__(
                'Total of %d email template(s) have been successfully imported.', $imported
            ));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bronto_email')->__('Please select template(s).'));
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Mass Action for Import
     */
    public function massDeleteAction()
    {
        $templateIds = $this->getRequest()->getParam('template_id', array());
        $deleteLevel = $this->getRequest()->getParam('delete_level', 'message');
        $deleted     = 0;

        // If single ID, set as array
        if (is_numeric($templateIds)) {
            $templateIds = array($templateIds);
        }

        // Begin Processing Templates
        if (count($templateIds) > 0) {
            foreach ($templateIds as $templateId) {
                if ('full' == $deleteLevel) {
                    $template = Mage::getModel('bronto_email/template')->load($templateId);
                } else {
                    $template = Mage::getModel('bronto_email/message')->load($templateId);
                }

                if ($template->getId()) {
                    try {
                        $template->delete();
                        $deleted++;
                    } catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__(
                'Total of %d email template(s) have been successfully deleted.', $deleted
            ));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bronto_email')->__('Please select template(s).'));
        }

        $this->_redirect('*/*');
    }

    public function updateSendTypeAction()
    {
        $templateIds = $this->getRequest()->getParam('template_id', array());
        $sendType    = $this->getRequest()->getParam('send_type', 'marketing');
        $updated     = 0;

        // If single ID, set as array
        if (is_numeric($templateIds)) {
            $templateIds = array($templateIds);
        }

        // Begin Processing Templates
        if (count($templateIds) > 0) {
            foreach ($templateIds as $templateId) {
                $template = Mage::getModel('bronto_email/message')->load($templateId);

                // TODO: When approval status is available from api, implement this check
                //                if ('transactional' == $sendType && 0 === $template->getBrontoMessageApproved()) {
                //                    Mage::helper('bronto_email')->writeError(
                //                        Mage::helper('bronto_email')->__($template->getTemplateCode() . ' has not been approved for transactional sending')
                //                    );
                //                }

                if ($template->getId()) {
                    try {
                        $template->setTemplateSendType($sendType);
                        $template->save();
                        $updated++;
                    } catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__(
                'Total of %d email template(s) have been successfully updated.', $updated
            ));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bronto_email')->__('Please select template(s).'));
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Load in all of Magento's default templates
     */
    public function loadDefaultsAction()
    {
        /** @var $templateMode Bronto_Email_Model_Template_Import */
        $templateModel = Mage::getModel('bronto_email/template');

        // Process Templates
        try {
            $templateModel->handleDefaultTemplates();
        } catch (Exception $e) {
            Mage::helper('bronto_email')->writeError($e->getMessage());
        }

        $this->_redirect('*/*/import');
    }

    /**
     * Preview Action to display Template Content
     */
    public function previewAction()
    {
        parent::previewAction();
    }

    /**
     * Load email template from request
     *
     * @param string $idFieldName
     *
     * @return Mage_Adminhtml_Model_Email_Template $model
     */
    protected function _initTemplate($idFieldName = 'template_id')
    {
        $this->_title($this->__('System'))->_title($this->__('Transactional Emails'));

        $id    = (int)$this->getRequest()->getParam($idFieldName);
        $model = Mage::getModel('bronto_email/template');

        if ($id) {
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
}
