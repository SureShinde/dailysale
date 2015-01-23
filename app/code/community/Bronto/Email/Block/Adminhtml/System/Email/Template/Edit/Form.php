<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Template_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Prepare layout.
     * Add files to use dialog windows
     *
     * @return Bronto_Email_Block_Adminhtml_System_Email_Template_Edit_Form
     */
    protected function _prepareLayout()
    {
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addItem('js', 'prototype/window.js')
                ->addItem('js_css', 'prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css')
                ->addItem('js', 'mage/adminhtml/variables.js');
        }

        return parent::_prepareLayout();
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        // If Bronto Email module not enabled, use Mage
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            $parent = new Mage_Adminhtml_Block_System_Email_Template_Edit_Form();

            return $parent->_prepareForm();
        }

        // Create New Form Element
        $form = new Varien_Data_Form();

        // Create New Form Fieldset Element
        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('adminhtml')->__('Template Information'),
            'class'  => 'fieldset-wide'
        ));

        // Get the TemplateID
        $templateId = $this->getEmailTemplate()->getId();

        // Build "Used Current/Default For"
        if ($templateId) {
            $fieldset->addField('used_currently_for', 'label', array(
                'label'              => Mage::helper('adminhtml')->__('Used Currently For'),
                'container_id'       => 'used_currently_for',
                'after_element_html' =>
                    '<script type="text/javascript">' .
                    (!$this->getEmailTemplate()->getSystemConfigPathsWhereUsedCurrently() ? '$(\'' . 'used_currently_for' . '\').hide(); ' : '') .
                    '</script>',
            ));
            if (!$this->getEmailTemplate()->getSystemConfigPathsWhereUsedCurrently()) {
                $fieldset->addField('used_default_for', 'label', array(
                    'label'              => Mage::helper('adminhtml')->__('Used as Default For'),
                    'container_id'       => 'used_default_for',
                    'after_element_html' =>
                        '<script type="text/javascript">' .
                        (!(bool)$this->getEmailTemplate()->getOrigTemplateCode() ? '$(\'' . 'used_default_for' . '\').hide(); ' : '') .
                        '</script>',
                ));

                $fieldset->addField('note_used_currently', 'label', array(
                    'label'              => '',
                    'container_id'       => 'note_used_currently',
                    'after_element_html' => '<div style="color:red;"><strong>Note:</strong> This Email Message is currently not used.</div>',
                ));
            }
        } else {
            $fieldset->addField('used_default_for', 'label', array(
                'label'              => Mage::helper('adminhtml')->__('Used as Default For'),
                'container_id'       => 'used_default_for',
                'after_element_html' =>
                    '<script type="text/javascript">' .
                    (!(bool)$this->getEmailTemplate()->getOrigTemplateCode() ? '$(\'' . 'used_default_for' . '\').hide(); ' : '') .
                    '</script>',
            ));
        }

        // If has more than one store, provide store select field,
        // Otherwise set hidden field with single store Id
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'select', array(
                'name'     => 'store_id',
                'label'    => Mage::helper('adminhtml')->__('Store View'),
                'title'    => Mage::helper('adminhtml')->__('Store View'),
                'onchange' => "updateMessages();",
                'required' => true,
                'values'   => $this->_getActiveStoreValuesForForm(true, true),
            ));
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'  => 'store_id',
                'value' => Mage::app()->getStore(true)->getId(),
            ));
        }

        // Create field for selecting How the template will be sent
        $sendtype = $fieldset->addField('template_send_type', 'select', array(
            'name'     => 'template_send_type',
            'label'    => Mage::helper('adminhtml')->__('Send Type'),
            'title'    => Mage::helper('adminhtml')->__('Send Type'),
            'onchange' => "updateMessages();",
            'required' => true,
            'values'   => array('magento' => 'Magento Email', 'marketing' => 'Bronto Marketing', 'transactional' => 'Bronto Transactional'),
        ));

        // Add Script after send type field to handle updating form
        $sendtype->setAfterElementHtml("
         <script type=\"text/javascript\">
            Event.observe(window, 'load', function() {
                triggerSendType($('template_send_type').value);
            });

            function triggerSendType(sendType) {
                if (sendType == 'magento') {
                    // Disable Some
                    $('bronto_message_id').disable();
                    $('send_flags').disable();
                    $('sales_rule').disable();
                    $$('#product_recommendation').each(function(elem) { elem.disable() });
                    $$('#container_product_recommendation').each(function(elem) { elem.hide() } );
                    $('orig_template_text').disable();
                    $('container_bronto_message_id').hide();
                    $('container_send_flags').hide();
                    $('container_sales_rule').hide();
                    $('container_orig_template_text').hide();

                    // Enable Others
                    $('template_subject').enable();
                    $('template_text').enable();
                    $('container_template_subject').show();
                    $('container_template_text').show();
                    $('insert_variable').show();

                    if ($('field_template_styles') != undefined) {
                        $('field_template_styles').show();
                        $('template_styles').enable();
                    }
                } else {
                    // Enable Some
                    $('bronto_message_id').enable();
                    $('send_flags').enable();
                    $('sales_rule').enable();
                    $$('#product_recommendation').each(function(elem) { elem.enable() } );
                    $$('#container_product_recommendation').each(function(elem) { elem.show() });
                    $('orig_template_text').enable();
                    $('container_bronto_message_id').show();
                    $('container_send_flags').show();
                    $('container_sales_rule').show();
                    $('container_orig_template_text').show();

                    // Disable Others
                    $('template_subject').disable();
                    $('template_text').disable();
                    $('container_template_subject').hide();
                    $('container_template_text').hide();
                    $('insert_variable').hide();

                    if ($('field_template_styles') != undefined) {
                        $('template_styles').disable();
                        $('field_template_styles').hide();
                    }
                }
            }

            function updateMessages(){
                 var storeId   = $('store_id').value;
                 var sendType  = $('template_send_type').value;
                 var template  = '{$templateId}';

                 triggerSendType(sendType);

                 if (sendType != 'magento') {
                    var reloadurl = '" . $this->getUrl('adminhtml/system_email_template/ajaxlist') . "template_id/'+template+'/id/'+storeId+'/type/'+sendType;
                    new Ajax.Request(reloadurl, {
                        method: 'get',
                        onLoading: function (transport) {
                            $('bronto_message_id').update('Searching...');
                        },
                        onComplete: function(transport) {
                            $('bronto_message_id').update(transport.responseText);
                        }
                    });
                }
            }

            function syncHiddenValue(element) {
                var fieldValue = element.value;

                if ($(element.id + '_hidden') != undefined) {
                    $(element.id + '_hidden').value = fieldValue;
                }
            }
        </script>");

        // Template Name/Code
        $fieldset->addField('template_code', 'text', array(
            'name'     => 'template_code',
            'label'    => Mage::helper('adminhtml')->__('Name'),
            'required' => true
        ));

        // Add hidden fields to hold backups of the necessary values
        $fieldset->addField('bronto_message_id_hidden', 'hidden', array('name' => 'bronto_message_id_hidden'));
        $fieldset->addField('sales_rule_hidden', 'hidden', array('name' => 'sales_rule_hidden'));
        $fieldset->addField('product_recommendation_hidden', 'hidden', array('name' => 'product_recommendation_hidden'));
        $fieldset->addField('template_subject_hidden', 'hidden', array('name' => 'template_subject_hidden'));
        $fieldset->addField('template_text_hidden', 'hidden', array('name' => 'template_text_hidden'));
        $fieldset->addField('template_styles_hidden', 'hidden', array('name' => 'template_styles_hidden'));

        // Used for magento send type
        $fieldset->addField('template_subject', 'text', array(
            'name'         => 'template_subject',
            'label'        => Mage::helper('adminhtml')->__('Template Subject'),
            'onchange'     => "syncHiddenValue(this);",
            'container_id' => 'container_template_subject',
            'required'     => true,
        ));

        // Create field to allow selecting Bronto Message to bind to template
        $fieldset->addField('bronto_message_id', 'select', array(
            'name'         => 'bronto_message_id',
            'label'        => Mage::helper('adminhtml')->__('Bronto Message'),
            'container_id' => 'container_bronto_message_id',
            'onchange'     => "syncHiddenValue(this);",
            'values'       => Mage::helper('bronto_email/message')->getAllMessageOptions(),
            'required'     => true,
        ));

        $fieldset->addField('send_flags', 'select', array(
            'name' => 'send_flags',
            'label' => Mage::helper('adminhtml')->__('Send Flags'),
            'title' => Mage::helper('adminhtml')->__('Send Flags'),
            'container_id' => 'container_send_flags',
            'values' => Mage::getModel('bronto_common/system_config_source_sendOptions')->toOptionArray(true),
            'note' => $this->__("Send flags for this message. The options are: <br/> - <strong>Sender Authentication</strong>: Will sign your message with DomainKeys/DKIM, optimizing your message delivery to Hotmail, MSN, and Yahoo! email addresses. <br/> - <strong>Fatigue Override</strong>: The delivery can be sent even if it exceeds the frequency cap settings for a customer. <br/> - <strong>Reply Tracking</strong>: Will store a copy of all replies to your messages on the Replies page within the Bronto platform.")
        ));

        // Create field to allow selecting a sales rule to pull a coupon code from
        $fieldset->addField('sales_rule', 'select', array(
            'name'         => 'sales_rule',
            'label'        => Mage::helper('adminhtml')->__('Shopping Cart Price Rule Coupon Code'),
            'note'         => $this->__('Use API tag <em>%%%%#couponCode%%%%</em> within your message in Bronto. You are responsible for ensuring the shopping cart price rule is active and valid, or else it may appear blank.'),
            'container_id' => 'container_sales_rule',
            'onchange'     => "syncHiddenValue(this);",
            'values'       => Mage::getModel('bronto_common/system_config_source_coupon')->toOptionArray(true),
            'required'     => false,
        ));

        if (Mage::helper('bronto_product')->isEnabledForAny()) {
            $fieldset->addField('product_recommendation', 'select', array(
                'name' => 'product_recommendation',
                'container_id' => 'container_product_recommendation',
                'onchange'     => "syncHiddenValue(this);",
                'label' => Mage::helper('adminhtml')->__('Product Recommendations'),
                'required' => false,
                'values' => Mage::getModel('bronto_product/recommendation')->toOptionArray(true),
                'note' => $this->__('Inject related product content into this message. Recommendations are created in <strong>Promotions</strong> &raquo; <strong>Bronto Product Recommendations')
            ));
        }

        // Display Variables that are available for the original template
        $fieldset->addField('template_variables_key', 'label', array(
            'container_id'       => 'template_variables_key_row',
            'label'              => Mage::helper('adminhtml')->__('Variables'),
            'after_element_html' => '<div id="template_variables_key_list"></div>' .
                ($templateId ? '' : '<script>$("template_variables_key_row").hide();</script>')
        ));

        // Display template text that was imported into Bronto
        $fieldset->addField('orig_template_text', 'textarea', array(
            'name'         => 'orig_template_text',
            'label'        => Mage::helper('adminhtml')->__('Original Template Content'),
            'note'         => $this->__('For Reference Only'),
            'container_id' => 'container_orig_template_text',
            'onchange'     => "syncHiddenValue(this);",
            'readonly'     => true,
            'style'        => 'height:24em;background-color:#efefef;',
        ));

        $fieldset->addField('orig_template_variables', 'hidden', array(
            'name' => 'orig_template_variables',
        ));

        $fieldset->addField('variables', 'hidden', array(
            'name'  => 'variables',
            'value' => Zend_Json::encode($this->getVariables())
        ));

        $fieldset->addField('template_variables', 'hidden', array(
            'name' => 'template_variables',
        ));

        // Used for magento send type
        $insertVariableButton = $this->getLayout()
            ->createBlock('adminhtml/widget_button', '', array(
                'type'         => 'button',
                'label'        => Mage::helper('adminhtml')->__('Insert Variable...'),
                'container_id' => 'container_widget_button',
                'onclick'      => 'templateControl.openVariableChooser();return false;',
            ));

        $fieldset->addField('insert_variable', 'note', array(
            'text' => $insertVariableButton->toHtml()
        ));

        $fieldset->addField('template_text', 'textarea', array(
            'name'         => 'template_text',
            'label'        => Mage::helper('adminhtml')->__('Template Content'),
            'title'        => Mage::helper('adminhtml')->__('Template Content'),
            'container_id' => 'container_template_text',
            'onchange'     => "syncHiddenValue(this);",
            'required'     => true,
            'style'        => 'height:24em',
        ));

        if (!$this->getEmailTemplate()->isPlain()) {
            $fieldset->addField('template_styles', 'textarea', array(
                'name'         => 'template_styles',
                'label'        => Mage::helper('adminhtml')->__('Template Styles'),
                'container_id' => 'field_template_styles',
                'onchange'     => "syncHiddenValue(this);",
            ));
        }

        if ($templateId) {
            if ($this->getEmailTemplate()->getUseDefaultSalesRule()) {
                $this->getEmailTemplate()->setSalesRule('default');
            }
            if ($this->getEmailTemplate()->getUseDefaultRec()) {
                $this->getEmailTemplate()->setProductRecommendation('default');
            }
            $form->addValues($this->getEmailTemplate()->getData());
            $form->addValues(array(
                'template_variables'       => Zend_Json::encode($this->getEmailTemplate()->getVariablesOptionArray(true)),
                // Populate hidden fields
                'bronto_message_id_hidden' => $this->getEmailTemplate()->getBrontoMessageId(),
                'sales_rule_hidden'        => $this->getEmailTemplate()->getSalesRule(),
                'template_subject_hidden'  => $this->getEmailTemplate()->getTemplateSubject(),
                'template_text_hidden'     => $this->getEmailTemplate()->getTemplateText(),
                'template_styles_hidden'   => $this->getEmailTemplate()->getTemplateStyles(),
                'product_recommendation_hidden' => $this->getEmailTemplate()->getProductRecommendation(),
            ));
        }

        if ($values = Mage::getSingleton('adminhtml/session')->getData('email_template_form_data', true)) {
            $form->setValues($values);
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Return current email template model
     *
     * @return Mage_Core_Model_Email_Template
     */
    public function getEmailTemplate()
    {
        return Mage::registry('current_email_template');
    }

    /**
     * Filter Store Options by stores where module is enabled
     *
     * @param bool $empty
     * @param bool $all
     *
     * @return array
     */
    protected function _getActiveStoreValuesForForm($empty = false, $all = false)
    {
        $storeOptions = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm($empty, $all);
        $curWebsite   = 0;
        $curWebCount  = 0;

        // Cycle Through Options
        foreach ($storeOptions as $optionId => $option) {
            if (is_array($option['value']) && empty($option['value'])) {
                if ($optionId != $curWebsite) {
                    if ($curWebCount == 0) {
                        unset($storeOptions[$curWebsite]);
                    }
                    $curWebCount = 0;
                }

                $curWebsite = $optionId;
            }

            // If Option has an array value with items
            if (is_array($option['value']) && count($option['value']) >= 1) {
                $curWebCount++;
                // Cycle Through Sub-Options
                foreach ($option['value'] as $subId => $subOption) {
                    // If Sub-Option value is a string and is numeric
                    if (is_string($subOption['value']) && is_numeric($subOption['value'])) {
                        // If Sub-Option Value is a store id and that store is not enabled, unset it's value
                        if (!Mage::helper('bronto_email')->isEnabled('store', $subOption['value'])) {
                            unset($storeOptions[$optionId]['value'][$subId]);
                            // If Option no longer has any values, remove Option
                            if (count($storeOptions[$optionId]['value']) < 1) {
                                $curWebCount--;
                                unset($storeOptions[$optionId]);
                            }
                        }
                    }
                }
            }
        }

        // If the last website has no groups, remove it
        if ($curWebCount == 0) {
            if (array_key_exists($curWebsite, $storeOptions)) {
                unset($storeOptions[$curWebsite]);
            }
        }

        return $storeOptions;
    }

    /**
     * Retrieve variables to insert into email
     *
     * @return array
     */
    public function getVariables()
    {
        $variables       = array();
        $variables[]     = Mage::getModel('core/source_email_variables')
            ->toOptionArray(true);
        $customVariables = Mage::getModel('core/variable')
            ->getVariablesOptionArray(true);
        if ($customVariables) {
            $variables[] = $customVariables;
        }
        /* @var $template Mage_Core_Model_Email_Template */
        $template = Mage::registry('current_email_template');
        if ($template->getId() && $templateVariables = $template->getVariablesOptionArray(true)) {
            $variables[] = $templateVariables;
        }

        return $variables;
    }

}
