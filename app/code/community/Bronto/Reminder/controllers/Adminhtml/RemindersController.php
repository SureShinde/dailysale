<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Adminhtml_RemindersController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialize module page
     *
     * @return Bronto_Reminder_Adminhtml_RemindersController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promo/bronto_reminder')
            ->_addBreadcrumb(
                Mage::helper('bronto_reminder')->__('Reminder Rules'),
                Mage::helper('bronto_reminder')->__('Reminder Rules')
            );

        return $this;
    }

    /**
     * Initialize proper rule model
     *
     * @param string $requestParam
     *
     * @return Bronto_Reminder_Model_Rule
     */
    protected function _initRule($requestParam = 'id')
    {
        $ruleId = $this->getRequest()->getParam($requestParam, 0);
        $rule   = Mage::getModel('bronto_reminder/rule');
        if ($ruleId) {
            $rule->load($ruleId);
            if (!$rule->getId()) {
                Mage::throwException($this->__('Wrong reminder rule requested.'));
            }
        }
        Mage::register('current_reminder_rule', $rule);

        return $rule;
    }

    /**
     * Index Action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title($this->__('Promotions'))->_title($this->__('Reminder Rules'));
        if (Mage::helper('bronto_reminder')->isEnabledForAny()) {
            $this->_initAction()
                ->_addContent($this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder'))
                ->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice('This module is currently disabled.  Please see ' . Mage::helper('bronto_reminder')->getConfigLink() . ' to enable.');
            $this->_initAction()->renderLayout();
        }

    }

    /**
     * Create new rule
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit reminder rule
     *
     * @return $this|Mage_Core_Controller_Varien_Action
     */
    public function editAction()
    {
        $this->_title($this->__('Promotions'))->_title($this->__('Bronto Email Reminder Rules'));

        try {
            /* @var $model Bronto_Reminder_Model_Rule */
            $model = $this->_initRule();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

            return $this->_redirect('*/*/');
        }

        if (!Mage::helper('bronto_reminder')->isAllowSendForAny()) {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('bronto_reminder')->getNotAllowedText());
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Rule'));

        // set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');

        $block = $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit')
            ->setData('form_action_url', $this->getUrl('*/*/save'));

        $this->_initAction();

        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true)
            ->addJs('mage/adminhtml/wysiwyg/widget.js')
            ->addItem('js_css', 'prototype/windows/themes/default.css')
            ->addItem('js_css', 'prototype/windows/themes/magento.css');

        $this->_addBreadcrumb(
            $model->getId() ? $this->__('Edit Rule') : $this->__('New Rule'),
            $model->getId() ? $this->__('Edit Rule') : $this->__('New Rule'))
            ->_addContent($block)
            ->_addLeft($this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tabs'))
            ->renderLayout();

        return $this;
    }

    /**
     * Add new condition
     *
     * @return void
     */
    public function newConditionHtmlAction()
    {
        $id      = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type    = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('bronto_reminder/rule'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    /**
     * Massages the POST data for Rule submission
     *
     * @param array $data
     *
     * @return array
     */
    protected function _prepareRuleFormData($data)
    {
        $data['conditions'] = $data['rule']['conditions'];

        if (!isset($data['website_ids'])) {
            $data['website_ids'] = array(Mage::app()->getStore(true)->getWebsiteId());
        }

        $data = $this->_filterDates($data, array('active_from', 'active_to'));

        return $data;
    }

    /**
     * Runs a form validation before attempting to save
     */
    public function validateAction()
    {
        $json = array();
        if ($data = $this->getRequest()->getPost()) {
            try {
                $this->_initRule('rule_id')
                    ->loadPost($this->_prepareRuleFormData($data))
                    ->getConditions()
                    ->getConditionsSql(null, new Zend_Db_Expr(':website_id'));
            } catch (Mage_Core_Exception $e) {
                $json['message'] = $e->getMessage();
            } catch (Exception $e) {
                $json['message'] = $this->__('Failed to validate reminder rule.');
            }
        }

        if (isset($json['message'])) {
            $json['error']   = true;
            $json['message'] = Mage::getBlockSingleton('core/messages')
                ->addError($json['message'])
                ->getGroupedHtml();
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($json));
    }

    /**
     * Save Reminder Rule
     *
     * @return $this|Mage_Core_Controller_Varien_Action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            try {
                $redirectBack = $this->getRequest()->getParam('back', false);

                $model = $this->_initRule('rule_id');
                $model->loadPost($this->_prepareRuleFormData($data));
                Mage::getSingleton('adminhtml/session')->setPageData($model->getData());
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The reminder rule has been saved.'));
                Mage::getSingleton('adminhtml/session')->setPageData(false);

                if ($redirectBack) {
                    return $this->_redirect(
                        '*/*/edit',
                        array(
                            'id'       => $model->getId(),
                            '_current' => true,
                        )
                    );
                }

            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setPageData($data);

                return $this->_redirect('*/*/edit', array('id' => $model->getId()));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Failed to save reminder rule.'));
                Mage::helper('bronto_reminder')->writeError($e);
            }
        }
        $this->_redirect('*/*/');

        return $this;
    }

    /**
     * Delete reminder rule
     *
     * @return void
     */
    public function deleteAction()
    {
        try {
            $model = $this->_initRule();
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The reminder rule has been deleted.'));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/edit', array('id' => $model->getId()));

            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Failed to delete reminder rule.'));
            Mage::helper('bronto_reminder')->writeError($e);
        }
        $this->_redirect('*/*/');
    }

    /**
     * Match reminder rule and send emails for matched customers
     *
     * @return void
     */
    public function runAction()
    {
        try {
            Mage::helper('bronto_reminder')->writeDebug("Admin pressed 'Run Now'...");
            $model  = $this->_initRule();
            $result = $model->sendReminderEmails();
            if ($result) {
                $total   = $result['total'];
                $success = $result['success'];
                $error   = $result['error'];
                Mage::getSingleton('adminhtml/session')->addSuccess(sprintf("Processed %d Reminders (%d Error / %d Success)", $total, $error, $success));
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Reminder rule sending failed.');
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::helper('bronto_reminder')->writeError($e);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addException($e, $this->__('Reminder rule matching error.'));
            Mage::helper('bronto_reminder')->writeError($e);
        }
        $this->_redirect('*/*/edit', array('id' => $model->getId(), 'active_tab' => 'matched_customers'));
    }

    /**
     * Match reminder rule
     *
     * @return void
     */
    public function matchAction()
    {
        try {
            Mage::helper('bronto_reminder')->writeDebug("Admin pressed 'Match Now'...");
            $model = $this->_initRule();
            $model->sendReminderEmails(true);
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The reminder rule has been matched.'));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::helper('bronto_reminder')->writeError($e);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addException($e, $this->__('Reminder rule matching error.'));
            Mage::helper('bronto_reminder')->writeError($e);
        }
        $this->_redirect('*/*/edit', array('id' => $model->getId(), 'active_tab' => 'matched_customers'));
    }

    /**
     * Customer grid ajax action
     *
     * @return void
     */
    public function customerGridAction()
    {
        if ($this->_initRule('rule_id')) {
            $block = $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tab_customers');
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * Customer grid ajax action
     *
     * @return void
     */
    public function guestGridAction()
    {
        if ($this->_initRule('rule_id')) {
            $block = $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tab_guests');
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * Convert dates in array from localized to internal format
     *
     * @param array $array
     * @param array $dateFields
     *
     * @return array
     */
    protected function _filterDates($array, $dateFields)
    {
        if (method_exists('Mage_Core_Controller_Varien_Action', '_filterDates')) {
            return parent::_filterDates($array, $dateFields);
        }

        if (empty($dateFields)) {
            return $array;
        }

        $filterInput = new Zend_Filter_LocalizedToNormalized(array(
            'date_format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));

        $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
            'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
        ));

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }

        return $array;
    }

    /**
     * Add an extra title to the end or one from the end, or remove all
     *
     * Usage examples:
     * $this->_title('foo')->_title('bar');
     * => bar / foo / <default title>
     *
     * $this->_title()->_title('foo')->_title('bar');
     * => bar / foo
     *
     * $this->_title('foo')->_title(false)->_title('bar');
     * bar / <default title>
     *
     * @see self::_renderTitles()
     *
     * @param null $text
     * @param bool $resetIfExists
     *
     * @return $this|Mage_Core_Controller_Varien_Action
     */
    protected function _title($text = null, $resetIfExists = true)
    {
        if (method_exists('Mage_Adminhtml_Controller_Action', '_title')) {
            return parent::_title($text, $resetIfExists);
        }

        if (is_string($text)) {
            $this->_titles[] = $text;
        } elseif (-1 === $text) {
            if (empty($this->_titles)) {
                $this->_removeDefaultTitle = true;
            } else {
                array_pop($this->_titles);
            }
        } elseif (empty($this->_titles) || $resetIfExists) {
            if (false === $text) {
                $this->_removeDefaultTitle = false;
                $this->_titles             = array();
            } elseif (null === $text) {
                $this->_removeDefaultTitle = true;
                $this->_titles             = array();
            }
        }

        return $this;
    }

    /**
     * Get if module is allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_isSectionAllowed('bronto_reminder');
    }

    /**
     * Check if specified section allowed in ACL
     *
     * Will forward to deniedAction(), if not allowed.
     *
     * @param string $section
     *
     * @return bool
     */
    protected function _isSectionAllowed($section)
    {
        try {
            $session        = Mage::getSingleton('admin/session');
            $resourceLookup = "admin/system/config/{$section}";
            if ($session->getData('acl') instanceof Mage_Admin_Model_Acl) {
                $resourceId = $session->getData('acl')->get($resourceLookup)->getResourceId();
                if (!$session->isAllowed($resourceId)) {
                    throw new Exception('');
                }

                return true;
            }
        } catch (Zend_Acl_Exception $e) {
            $this->norouteAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        } catch (Exception $e) {
            $this->deniedAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        }

        return false;
    }
}
