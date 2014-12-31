<?php

class TM_NewsletterBooster_Block_Adminhtml_Template_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare layout.
     * Add files to use dialog windows
     *
     * @return Mage_Adminhtml_Block_System_Email_Template_Edit_Form
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
     * Add fields to form and create template info form
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $campaign = $form->addFieldset('campaign_fieldset', array(
            'legend' => Mage::helper('newsletterbooster')->__('Campaign Settings'),
            'class' => 'fieldset-wide'
        ));

        $segmentsOptions = Mage::getModel('segmentationsuite/segments')->getOptionArray();
        $gatewayOptions = Mage::getModel('newsletterbooster/gateway')->getOptionArray();
        
        $campaign->addField('template_code', 'text', array(
            'name'=>'template_code',
            'label' => Mage::helper('newsletterbooster')->__('Title'),
            'required' => true
        ));
        
        $campaign->addField('save_and_queue', 'hidden', array(
            'name'=>'save_and_queue',
            'label' => Mage::helper('newsletterbooster')->__('Title'),
            'required' => false
        ));
        
        $campaign->addField('description', 'textarea', array(
            'name'=>'description',
            'label' => Mage::helper('newsletterbooster')->__('Description'),
            'required' => false
        ));
        
        $campaign->addField('tm_segment', 'multiselect', array(
            'label'     => Mage::helper('newsletterbooster')->__('Customer segment'),
            'title'     => Mage::helper('newsletterbooster')->__('Customer segment'),
            'name'      => 'tm_segment',
            'required'  => false,
            'values'    => $segmentsOptions
        ));

        $campaign->addField('tm_gateway', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Email gateway'),
            'title'     => Mage::helper('newsletterbooster')->__('Email gateway'),
            'name'      => 'tm_gateway',
            'required'  => true,
            'options'   => $gatewayOptions
        ));
        
        $campaign->addField('in_frontend', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Visible in frontend'),
            'title'     => Mage::helper('newsletterbooster')->__('Visible in frontend'),
            'name'      => 'in_frontend',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('newsletterbooster')->__('Yes'),
                '0' => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        $campaign->addField('sent_guest', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Send For NewsletterBooster Subscribers'),
            'title'     => Mage::helper('newsletterbooster')->__('Send For NewsletterBooster Subscribers'),
            'name'      => 'sent_guest',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('newsletterbooster')->__('Yes'),
                '0' => Mage::helper('newsletterbooster')->__('No')
            )
        ));
        
        $campaign->addField('track_opens', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Track Opens'),
            'title'     => Mage::helper('newsletterbooster')->__('Track Opens'),
            'name'      => 'track_opens',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('newsletterbooster')->__('Yes'),
                '0' => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        $campaign->addField('track_clicks', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Track Clicks'),
            'title'     => Mage::helper('newsletterbooster')->__('Track Clicks'),
            'name'      => 'track_clicks',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('newsletterbooster')->__('Yes'),
                '0' => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        $campaign->addField('google_analitics', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Track customers clicks with Google Analytics'),
            'title'     => Mage::helper('newsletterbooster')->__('Track customers clicks with Google Analytics'),
            'name'      => 'google_analitics',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('newsletterbooster')->__('Yes'),
                '0' => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        $campaign->addField('google_source', 'text', array(
            'name'=>'google_source',
            'label' => Mage::helper('newsletterbooster')->__('Goodle Campaign Source'),
            'required' => false,
            'after_element_html' =>
                '<small>' . Mage::helper('newsletterbooster')->__('Use utm_source to identify a search engine, newsletter name, or other source.
Example: utm_source=google') . '</small>'
        ));

        $campaign->addField('google_medium', 'text', array(
            'name'=>'google_medium',
            'label' => Mage::helper('newsletterbooster')->__('Google Campaign Medium'),
            'required' => false,
            'after_element_html' =>
                '<small>' . Mage::helper('newsletterbooster')->__('Use utm_medium to identify a medium such as email or cost-per- click.
Example: utm_medium=email') . '</small>'
        ));

        $campaign->addField('google_title', 'text', array(
            'name'=>'google_title',
            'label' => Mage::helper('newsletterbooster')->__('Google Campaign Name'),
            'required' => false,
            'after_element_html' =>
                '<small>' . Mage::helper('newsletterbooster')->__('Used for keyword analysis. Use utm_campaign to identify a specific product promotion or strategic campaign.
Example: utm_campaign=spring_sale') . '</small>'
        ));
        
        $campaign->addField('google_content', 'text', array(
            'name'=>'google_content',
            'label' => Mage::helper('newsletterbooster')->__('Google Campaign Content'),
            'required' => false,
            'after_element_html' =>
                '<small>' . Mage::helper('newsletterbooster')->__('Used for keyword analysis. Use utm_content to identify a specific product promotion or strategic campaign.
Example: utm_content=nbsp528') . '</small>'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('newsletterbooster')->__('Template Information'),
            'class' => 'fieldset-wide'
        ));
        $templateId = $this->getEmailTemplate()->getCampaignId();
        if ($templateId) {
            $fieldset->addField('used_currently_for', 'label', array(
                'label' => Mage::helper('adminhtml')->__('Used Currently For'),
                'container_id' => 'used_currently_for',
                'after_element_html' =>
                '<script type="text/javascript">' .
                (!$this->getEmailTemplate()->getSystemConfigPathsWhereUsedCurrently()
                        ? '$(\'' . 'used_currently_for' . '\').hide(); ' : '') .
                '</script>',
            ));
        }

        if (!$templateId) {
            $fieldset->addField('used_default_for', 'label', array(
                'label' => Mage::helper('adminhtml')->__('Used as Default For'),
                'container_id' => 'used_default_for',
                'after_element_html' =>
                    '<script type="text/javascript">' .
                    (!(bool)$this->getEmailTemplate()->getOrigTemplateCode()
                        ? '$(\'' . 'used_default_for' . '\').hide(); ' : '') .
                    '</script>',
            ));
        }

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('stores','multiselect',array(
                'name'          => 'stores[]',
                'label'         => Mage::helper('newsletterbooster')->__('Apply design from'),
                'image'         => $this->getSkinUrl('images/grid-cal.gif'),
                'values'        => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm()
            ));
        } else {
            $fieldset->addField('stores', 'hidden', array(
                'name'      => 'stores[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
        }

        $fieldset->addField('test_email', 'text', array(
            'name'=>'test_email',
            'label' => Mage::helper('newsletterbooster')->__('Test email address'),
            'required' => false
        ));

        $fieldset->addField('template_subject', 'text', array(
            'name'=>'template_subject',
            'label' => Mage::helper('newsletterbooster')->__('Email subject'),
            'required' => true
        ));

        $fieldset->addField('template_sender_name', 'text', array(
            'name'=>'template_sender_name',
            'label' => Mage::helper('newsletterbooster')->__('From Name'),
            'required' => true
        ));

        $fieldset->addField('template_sender_email', 'text', array(
            'name'=>'template_sender_email',
            'label' => Mage::helper('newsletterbooster')->__('From Email'),
            'required' => true
        ));

        $fieldset->addField('orig_template_variables', 'hidden', array(
            'name' => 'orig_template_variables',
        ));

        $fieldset->addField('variables', 'hidden', array(
            'name' => 'variables',
            'value' => Zend_Json::encode($this->getVariables())
        ));

        $fieldset->addField('template_variables', 'hidden', array(
            'name' => 'template_variables',
        ));

        $insertVariableButton = $this->getLayout()
            ->createBlock('adminhtml/widget_button', '', array(
                'type' => 'button',
                'label' => Mage::helper('newsletterbooster')->__('Insert Variable...'),
                'onclick' => 'templateControl.openVariableChooser();return false;'
        ));

        $fieldset->addField('insert_variable', 'note', array(
                'text' => $insertVariableButton->toHtml()
        ));

        $fieldset->addField('template_text', 'editor', array(
            'name'      => 'template_text',
            'label'     => Mage::helper('newsletterbooster')->__('Template Content'),
            'title'     => Mage::helper('newsletterbooster')->__('Template Content'),
            'style'     => 'height:25em',
            'hidden'    => true,
            'config'    => Mage::getSingleton('cms/wysiwyg_config')->getConfig(
                array('hidden' => true)
            ),
            'wysiwyg'   => true,
            'required'  => true,
        ));

        if (!$this->getEmailTemplate()->isPlain()) {
            $fieldset->addField('template_styles', 'textarea', array(
                'name'=>'template_styles',
                'label' => Mage::helper('newsletterbooster')->__('Template Styles'),
                'container_id' => 'field_template_styles'
            ));
        }
        if ($templateId) {
            $fieldset->addField('campaign_id', 'hidden', array(
                'name' => 'campaign_id',
            ));
        }

        if ($templateId) {
            $form->addValues($this->getEmailTemplate()->getData());
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
     * Retrieve variables to insert into email
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = array();

        $variables[] = Mage::getModel('core/source_email_variables')
            ->toOptionArray(true);

        $customVariables = Mage::getModel('core/variable')
            ->getVariablesOptionArray(true);
        if ($customVariables) {
            $variables[] = $customVariables;
        }
        /* @var $template Mage_Core_Model_Email_Template */
        $template = Mage::registry('current_email_template');
        if ($template->getCampaignId() && $templateVariables = $template->getVariablesOptionArray(true)) {
            $variables[] = $templateVariables;
        }
        $variables[] = $this->getDefaulsVariables();

        return $variables;
    }

    public function getDefaulsVariables()
    {
        $optionArray = array();
        $optionArray[] = array(
            'value' => '{{var customer.name}}',
            'label' => Mage::helper('newsletterbooster')->__('Customer Name')
        );
        $optionArray[] = array(
            'value' => '{{var customer.email}}',
            'label' => Mage::helper('newsletterbooster')->__('Customer Email')
        );
        $optionArray[] = array(
            'value' => '{{var campaign.getUnsubscriptionLink()}}',
            'label' => Mage::helper('newsletterbooster')->__('Unsubscribe Campaign')
        );
        $optionArray = array(
            'label' => Mage::helper('newsletterbooster')->__('Default Variables'),
            'value' => $optionArray
        );

        return $optionArray;
    }
}
