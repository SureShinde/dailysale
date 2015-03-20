<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Message_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Prepares the message form
     * @see parent
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form('message_form', array(
            'id' => 'message_form',
        ));

        $fieldset = $form->addFieldset('message_fieldset', array(
            'comment' => $this->__('If a message is selected, a test delivery will be made to the email entered. This means that if a contact does not exist with the supplied email, it will be created. The same rules apply to test deliveries as they do marketing messages, so the contact must either be onboarding or active in order to receive the message.')
        ));

        $fieldset->addField('message_id', 'select', array(
            'name' => 'message_id',
            'label' => $this->__('Bronto Message'),
            'values' => Mage::helper('bronto_common/message')->getMessagesOptionsArray($this->getRequest()->getParam('store', null))
        ));

        $fieldset->addField('email_address', 'text', array(
            'name' => 'email_address',
            'label' => $this->__('Email Address'),
        ));

        $form->setValues(array(
            'email_address' => Mage::getSingleton('admin/session')->getUser()->getEmail()));
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
