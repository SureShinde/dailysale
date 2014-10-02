<?php

class TM_NewsletterBooster_Block_Adminhtml_Gateway_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('newsletterbooster_subscriber_form');
        $this->setTitle(Mage::helper('newsletterbooster')->__('Gateway Information'));
    }

    protected function _prepareForm()
    {
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $id)),
                'method' => 'post',
            )
        );

        if (Mage::registry('newsletterbooster_gateway') ) {
            $data = Mage::registry('newsletterbooster_gateway')->getData();
        }

        $fieldset = $form->addFieldset(
            'gateway_general_form',
            array('legend' => Mage::helper('newsletterbooster')->__('Email Gateway Details'))
        );
        $fieldset->addField('id', 'hidden', array(
        //  'class'     => 'required-entry',
            'name'      => 'id'
        ));

        $fieldset->addField('name', 'text', array(
            'label'     => Mage::helper('newsletterbooster')->__('Title'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'name',
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Status'),
            'name'      => 'status',
            'required'  => true,
            'values'    => array(
                array(
                    'value'     => 0,
                    'label'     => Mage::helper('newsletterbooster')->__('Disabled')
                ),
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('newsletterbooster')->__('Enabled')
                )
            ),
        ));

        $fieldset->addField('host', 'text', array(
            'label'     => Mage::helper('newsletterbooster')->__('Host'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'host',
        ));

        $fieldset->addField('user', 'text', array(
            'label'     => Mage::helper('newsletterbooster')->__('User'),
            'required'  => false,
            'name'      => 'user',
        ));

        $fieldset->addField('password', 'password', array(
            'label'     => Mage::helper('newsletterbooster')->__('Password'),
            'required'  => false,
            'name'      => 'password',
        ));

        $fieldset->addField('port', 'text', array(
            'label'     => Mage::helper('newsletterbooster')->__('Port'),
            'required'  => false,
            'name'      => 'port',
        ));

        $fieldset->addField('secure', 'select', array(
            'label'     => Mage::helper('newsletterbooster')->__('Secure'),
            'name'      => 'secure',
            'values'    => array(
                array(
                    'value'     => false,
                    'label'     => Mage::helper('newsletterbooster')->__('None'),
                ),
                array(
                    'value'     => 'tls',
                    'label'     => Mage::helper('newsletterbooster')->__('SSL/TLS'),
                ),
                array(
                    'value'     => 'ssl',
                    'label'     => Mage::helper('newsletterbooster')->__('STARTTLS'),
                )
            ),
        ));
        /*
        $fieldset->addField('remove', 'select', array(
            'label'     => Mage::helper('helpmate')->__('Remove'),
            'name'      => 'remove',
            'required'  => true,
            'values'    => array(

                array(
                    'value'     => 0,
                    'label'     => Mage::helper('helpmate')->__('Disabled')
                ),
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('helpmate')->__('Enabled')
                )
            ),
        ));
        */
        $form->setValues($data);

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}