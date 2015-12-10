<?php

class Bronto_Product_Adminhtml_RecommendationsController extends Mage_Adminhtml_Controller_Action
{
    protected $_header = 'Product Recommendations';
    protected $_module = 'bronto_product';
    protected $_helper;

    /**
     * Gets the product helper related to this module
     *
     * @return Bronto_Product_Helper_Data
     */
    public function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper($this->_module);
        }
        return $this->_helper;
    }

    /**
     * Gets the block for the grid for certain things
     *
     * @return Mage_Adminhtml_Block_Abstract
     */
    public function getBlock($key)
    {
        return $this->getLayout()
            ->createBlock("{$this->_module}/adminhtml_system_{$key}", $key);
    }

    /**
     * Initialize module page
     *
     * @return Bronto_Product_Adminhtml_RecommendationsController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promo/bronto_product')
            ->_addBreadcrumb(
                $this->getHelper()->__($this->_header),
                $this->getHelper()->__($this->_header)
            );
        return $this;
    }

    /**
     * Initialize the modal headers for products and message sending
     *
     * @return Bronto_Product_Adminhtml_RecommendationsController
     */
    protected function _modalStyles()
    {
        $head = $this->getLayout()->getBlock('head');
        $head->addItem('js_css', 'prototype/windows/themes/default.css');
        $enabled = Mage::getSingleton('cms/wysiwyg_config')->isEnabled();
        if (Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), '1', array('6', array('edition' => 'Professional', 'major' => '9'), '10', '11'))) {
            $head->addItem('js_css', 'prototype/windows/themes/magento.css');
            if ($enabled) {
                $head->addItem('js_css', 'mage/adminhtml/wysiwyg/tiny_mce/setup.js');
            }
        } else {
            $head->addCss('lib/prototype/windows/themes/magento.css');
            if ($enabled) {
                $head->addJs('mage/adminhtml/wysiwyg/tiny_mce/setup.js');
            }
        }
        if ($enabled) {
            $head->setCanLoadTinyMce(true);
        }
        return $this;
    }

    /**
     * Instantiates and loads the given product recommendation
     *
     * @param string $idField
     * @return Bronto_Product_Model_Recommendation
     */
    protected function _initRecommendation($idField = 'entity_id')
    {
        $this
            ->_title($this->__('Promotions'))
            ->_title($this->__($this->_header));

        $id = (int)$this->getRequest()->getParam($idField);
        $type = $this->getRequest()->getParam('type', Bronto_Product_Model_Recommendation::TYPE_API);

        $model = Mage::getModel('bronto_product/recommendation')
            ->setNumberOfItems(5)
            ->setContentType($type)
            ->load($id);

        if (!Mage::registry('product_recommendation')) {
            Mage::register('product_recommendation', $model);
        }

        if (!Mage::registry('current_product_recommendation')) {
            Mage::register('current_product_recommendation', $model);
        }

        return $model;
    }

    /**
     * Main grid for all product recommendations defined by the user
     *
     * @return Bronto_Product_Adminhtml_RecommendationsController
     */
    public function indexAction()
    {
        $this
            ->_title($this->__('Promotions'))
            ->_title($this->__($this->_header));
        if (!$this->getHelper()->isEnabledForAny()) {
            $url = $this->getUrl('*/system_config/edit', array('section' => $this->_module));
            $link = '<a href="' . $url . '">' . $this->getHelper()->__('Product Recommendations') . '</a>';
            Mage::getSingleton('adminhtml/session')->addNotice(
                $this->getHelper()->__('This module is currently disabled. Please see System &rsaquo; Configuration &raquo; Bronto &rsaquo; ') . $link . $this->getHelper()->__(' to enable.'));
        }
        $this->_initAction()->_addContent($this->getBlock('recommendation'));
        $this->renderLayout();
        return $this;
    }

    /**
     * Validates the model, by either setting the required fields
     * or throwing exceptions
     *
     * @return Bronto_Product_Model_Recommendation
     */
    protected function _validateRecommendation($model)
    {
        $name = $this->getRequest()->getParam('name', null);
        $content = $this->getRequest()->getParam('tag_content', null);
        $storeId = $this->getRequest()->getParam('store_id', '0');
        $numberOfItems = $this->getRequest()->getParam('number_of_items');
        $primarySource = $this->getRequest()->getParam('primary_source');

        if (empty($name)) {
            throw new RuntimeException('Name cannot be empty.');
        }

        if (!is_numeric($numberOfItems)) {
            throw new RuntimeException("Number of Items must be a number.");
        }

        if (empty($primarySource)) {
            throw new RuntimeException("Primary Source cannot be empty.");
        }

        if ($model->isContentTag()) {
            if (empty($content)) {
                throw new RuntimeException("Content Template cannot be empty.");
            }

            if (is_null($storeId)) {
                throw new RuntimeException("Store View cannot be empty.");
            }

            $other = current($model->getCollection()
                ->addNameToFilter($name)
                ->addAnyStoreFilter($storeId)
                ->onlyContentTagBased()
                ->getItems());

            if ($other && $other->getId() != $model->getId()) {
                throw new RuntimeException("Another content tag recommendation already exists with name $name for this store view.");
            }
        }

        return $model
            ->setName($name)
            ->setNumberOfItems((int) $numberOfItems)
            ->setTagContent($content)
            ->setStoreId($storeId)
            ->setPrimarySource($primarySource);
    }

    /**
     * Validates the recommendation via an AJAX call
     */
    public function validateAction()
    {
        $json = array();
        try {
            $this->_validateRecommendation($this->_initRecommendation());
        } catch (Exception $e) {
            $json['message'] = $e->getMessage();
        }

        if (isset($json['message'])) {
            $json['error'] = true;
            $json['message'] = Mage::getBlockSingleton('core/messages')
                ->addError($json['message'])
                ->getGroupedHtml();
        }
        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($json));
    }

    /**
     * Forwards calls to the new action
     */
    public function editAction()
    {
        $this->_forward('new');
    }

    /**
     * Renders the form for product receommendations
     */
    public function newAction()
    {
        $this->_initAction()->_modalStyles();

        $rec = $this->_initRecommendation('id');
        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(
                $this->__('Edit Recommendation'),
                $this->__('Edit Product Recommendation'));
        } else {
            $this->_addBreadcrumb(
                $this->__('New Recommendation'),
                $this->__('New Product Recommendation'));
        }
        $this->_title($rec->hasEntityId() ? $rec->getName() : $this->__('New Recommendation'));
        $this->_addContent($this->getBlock('recommendation_edit')->setEditMode($rec->hasEntityId()));
        $this->renderLayout();
    }

    /**
     * Deletes one or more recommendations
     */
    public function deleteAction()
    {
        $recIds = $this->getRequest()->getParam('id', array());
        $deleted = 0;
        if (is_numeric($recIds)) {
            $recIds = array($recIds);
        }

        $session = Mage::getSingleton('adminhtml/session');
        if (count($recIds) > 0) {
            foreach ($recIds as $recId) {
                $rec = Mage::getModel('bronto_product/recommendation')->load($recId);
                if ($rec->hasEntityId()) {
                    try {
                        $rec->delete();
                        $deleted++;
                    } catch (Exception $e) {
                        $session->addError($e->getMessage());
                    }
                }
            }
            $session->addSuccess($this->__('Total of %d product recommendation(s) have been successfully deleted.', $deleted));
        } else {
            $session->addError($this->__('Please select recommendation(s).'));
        }
        $this->_redirect("*/*");
    }

    /**
     * Copies one or more recommendations
     */
    public function copyAction()
    {
        $recIds = $this->getRequest()->getParam('id', array());
        $copied = 0;
        if (is_numeric($recIds)) {
            $recIds = array($recIds);
        }

        $session = Mage::getSingleton('adminhtml/session');
        if (count($recIds) > 0) {
            foreach ($recIds as $recId) {
                $rec = Mage::getModel('bronto_product/recommendation')->load($recId);
                if ($rec->hasEntityId()) {
                    try {
                        $rec->softCopy()->save();
                        $copied++;
                    } catch (Exception $e) {
                        $session->addError($e->getMessage());
                    }
                }
            }
            $session->addSuccess($this->__('Total of %d product recommendation(s) have been successfully copied.', $copied));
        } else {
            $session->addError($this->__('Please select recommendation(s).'));
        }
        $this->_redirect('*/*');
    }

    /**
     * Save or update a recommendation
     */
    public function saveAction()
    {
        $recommendation = $this->_initRecommendation();
        $session = Mage::getSingleton('adminhtml/session');

        if (!$recommendation->hasEntityId() && $this->getRequest()->getParam('id')) {
            $session->addError($this->__('This Product Recommendation no longer exists.'));
            return $this->_redirect('*/*/');
        }

        try {
            $this->_validateRecommendation($recommendation);
            foreach ($this->getRequest()->getParams() as $name => $value) {
                if (preg_match('/source$/', $name)) {
                    $recommendation->setData($name, $value);
                }
            }
            $recommendation->save();
            $session->addSuccess($this->__('The Product Recommendation has been saved.'));
            if ($this->getRequest()->getParam('continue')) {
                return $this->_redirect('*/*/edit', array('id' => $recommendation->getId()));
            } else {
                return $this->_redirect('*/*/');
            }
        } catch (Exception $e) {
            $session->setData('product_recommendation_data', $this->getRequest()->getParams());
            $session->addError($e->getMessage());
            return $this->_forward('new');
        }

        return $this;
    }

    /**
     * Display the preview form
     */
    public function previewAction()
    {
        $recommendation = $this
            ->_initAction()
            ->_modalStyles()
            ->_initRecommendation('entity_id');

        $this->_addBreadcrumb(
            $this->_title($this->__('Preview Recommendation')),
            $this->_title($this->__('Preview Product Recommendations')));
        $this->_addContent($this->getBlock('recommendation_preview')->setEditMode($recommendation->hasEntityId()));
        $this->renderLayout();
    }

    /**
     * Display the grid for products
     */
    public function selectedAction()
    {
        $this->_initRecommendation('id');
        $this->getResponse()->setBody(
          '<div style="height: 360px; overflow: auto">' .
          $this->getBlock('recommendation_selected_grid')->toHtml() .
          '</div>'
        );
    }

    /**
     * Fills the message form depending on the store view
     */
    public function messagesAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getBlock('recommendation_message_form')->toHtml());
    }

    /**
     * Sends the test email to the test contact
     */
    public function sendMessageAction()
    {
        $json = array();
        try {
            $grid = $this->getBlock('recommendation_preview_grid');
            $emailAddress = $this->getRequest()->getPost('email_address');
            $messageId = $this->getRequest()->getPost('message_id');
            $storeId = $this->getRequest()->getParam('store', 0);

            $json['missingParentRequirement'] = false;
            if (!$grid->getSelectedRecommendation()->hasEntityId()) {
                $json['missingParentRequirement'] = true;
                throw new RuntimeException('Please select a product recommendation.');
            }

            if (empty($emailAddress) || !Zend_Validate::is($emailAddress, 'EmailAddress')) {
                throw new RuntimeException('Email address is not valid.');
            }

            if (empty($messageId)) {
                throw new RuntimeException('Please select a message ID.');
            }

            $contact = Mage::helper('bronto_common/contact')->getContactByEmail($emailAddress, 'Magento Test Delivery', $storeId);
            if (empty($contact->id)) {
                $contact->status = Bronto_Api_Contact::STATUS_ONBOARDING;
                $contact->save();
            }

            $api = Mage::helper('bronto_common')->getApi(null, 'store', $storeId);
            $deliveryObject = $api->getDeliveryObject();
            $delivery = $deliveryObject->createRow();
            $delivery->messageId = $messageId;
            $delivery->fromEmail = $emailAddress;
            $delivery->fromName = 'Product Recommendation';
            $delivery->replyEmail = $emailAddress;
            $delivery->type = 'test';
            $delivery->start = date('c');
            $delivery->recipients = array(
                array(
                    'type' => 'contact',
                    'id' => $contact->id,
                    'deliveryType' => 'selected'
            ));

            $appEmulation = Mage::getSingleton('core/app_emulation');
            $emulatedInfo = $appEmulation->startEnvironmentEmulation($storeId);
            Mage::helper('bronto_product')->collectAndSetFields(
                $grid->getSelectedRecommendation(),
                $grid->getOptionalProducts(),
                $delivery,
                $storeId);
            $appEmulation->stopEnvironmentEmulation($emulatedInfo);

            $deliveryObject->add($delivery);
            $json['success'] = true;
            $json['message'] = Mage::getBlockSingleton('core/messages')
                ->addSuccess($this->__('Test message successfully send to ' . $emailAddress . '.'))
                ->getGroupedHtml();
        } catch (Exception $e) {
            $json['success'] = false;
            $json['message'] = $json['missingParentRequirement'] ?
                Mage::getBlockSingleton('core/messages')
                    ->addError($e->getMessage())
                    ->getGroupedHtml() :
                $e->getMessage();
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($json));
    }

    /**
     * Ajax response for the preview info
     */
    public function previewGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getBlock('recommendation_preview_grid')->toHtml());
    }

    /**
     * Handle AJAX post of tag info
     */
    public function contentAction()
    {
        $model = Mage::getModel('bronto_product/recommendation')->setData($this->getRequest()->getParams());
        if (!$model->isDynamicContent()) {
            $html = Mage::getBlockSingleton('core/messages')
                ->addError('Content does not appear to be formatted correctly.')
                ->getGroupedHtml();
        } else {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $emulatedInfo = $appEmulation->startEnvironmentEmulation($model->getStoreId());
            $html = $this->getHelper()->processTagContent($model, $model->getStoreId());
            $appEmulation->stopEnvironmentEmulation($emulatedInfo);
        }
        $this->getResponse()->setBody($html);
    }

    /**
     * Triggers a cron run for the product recs
     */
    public function runAction()
    {
        $helper = Mage::helper('bronto_product');
        $result = Mage::getModel('bronto_product/observer')->processContentTagsForScope();
        $this->_getSession()->addSuccess(sprintf("Processed %d Content Tags (%d Error / %d Success)", $result['total'], $result['error'], $result['success']));
        $returnParams = array('section' => 'bronto_product');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }
}
