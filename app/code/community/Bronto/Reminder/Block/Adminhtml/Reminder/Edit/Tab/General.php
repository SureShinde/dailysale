<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form  = new Varien_Data_Form();
        $model = Mage::registry('current_reminder_rule');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'  => Mage::helper('bronto_reminder')->__('General'),
        ));

        if ($model->getId()) {
            $fieldset->addField('rule_id', 'hidden', array(
                'name' => 'rule_id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => Mage::helper('bronto_reminder')->__('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('description', 'textarea', array(
            'name'  => 'description',
            'label' => Mage::helper('bronto_reminder')->__('Description'),
            'style' => 'width: 98%; height: 100px;',
        ));

        $fieldset->addField("salesrule_id", 'select', array(
            'name'     => "salesrule_id",
            'required' => false,
            'label'    => $this->__('Shopping Cart Price Rule Coupon Code'),
            'note'     => $this->__('Use API tag <em>%%%%#couponCode%%%%</em> within your message in Bronto. You are responsible for ensuring the Shopping Cart Price Rule is active and valid, or else it may appear blank.'),
            'values'   => Mage::helper('bronto_common/salesrule')->getRuleOptionsArray(),
        ));

        if (Mage::helper('bronto_product')->isEnabledForAny()) {
            $fieldset->addField("product_recommendation_id", 'select', array(
                'name' => "product_recommendation_id",
                'required' => false,
                'label' => $this->__('Product Recommendations'),
                'values' => Mage::getModel('bronto_product/recommendation')->toOptionArray('None Selected'),
                'note' => $this->__('Inject related product content into this message. Recommendations are created in <strong>Promotions</strong> &raquo; <strong>Bronto Product Recommendations')
            ));
        }

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('website_ids', 'multiselect', array(
                'name'     => 'website_ids',
                'required' => true,
                'label'    => Mage::helper('newsletter')->__('Assigned to Websites'),
                'values'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteValuesForForm(),
                'value'    => $model->getWebsiteIds()
            ));
        }

        $fieldset->addField('is_active', 'select', array(
            'label'    => Mage::helper('bronto_reminder')->__('Status'),
            'name'     => 'is_active',
            'required' => true,
            'options'  => array(
                '1' => Mage::helper('bronto_reminder')->__('Active'),
                '0' => Mage::helper('bronto_reminder')->__('Inactive'),
            ),
        ));

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);

        $fieldset->addField('active_from', 'date', array(
            'name'         => 'active_from',
            'label'        => Mage::helper('bronto_reminder')->__('Active From'),
            'title'        => Mage::helper('bronto_reminder')->__('Active From'),
            'image'        => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('active_to', 'date', array(
            'name'         => 'active_to',
            'label'        => Mage::helper('bronto_reminder')->__('Active To'),
            'title'        => Mage::helper('bronto_reminder')->__('Active To'),
            'image'        => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('send_to', 'select', array(
            'name'   => 'send_to',
            'label'  => Mage::helper('bronto_reminder')->__('Send To'),
            'title'  => Mage::helper('bronto_reminder')->__('Send To'),
            'values' => array(
                'both'  => Mage::helper('bronto_reminder')->__('Registered and Guest Users'),
                'user'  => Mage::helper('bronto_reminder')->__('Registered Users'),
                'guest' => Mage::helper('bronto_reminder')->__('Guests'),
            ),
            'value'  => 'both',
            'note'   => Mage::helper('bronto_reminder')->__('<strong>Note:</strong> Set to `Registered Users` or `Registered and Guest Users` if conditions will be applied to Wishlists, as only Registered Users can have Wishlists.'),
        ));

        $fieldset->addField('send_limit', 'text', array(
            'name'  => 'send_limit',
            'label' => Mage::helper('bronto_reminder')->__('Send Limit'),
            'title' => Mage::helper('bronto_reminder')->__('Send Limit'),
            'value' => '1',
            'class' => 'validate-digits validate-digits-range digits-range-0-',
            'note'  => Mage::helper('bronto_reminder')->__('This setting limits the number of times a single user will be sent this reminder email.  0 = unlimited.<br /><em>Default: 1</em>'),
        ));

        if (!$model->getId()) {
            $model->setData('send_limit', '1');
        }

        //        $subfieldset = $form->addFieldset('sub_fieldset', array(
        //            'legend'  => Mage::helper('bronto_reminder')->__('Repeat Schedule'),
        //            'comment' => '
        //                By default, a rule will only send a Reminder Email to a customer once.
        //                To allow a rule to re-send a message (as long as the conditions still match) to a customer, you must configure the Repeat Schedule.
        //            ',
        //        ));
        //
        //        $subfieldset->addField('schedule', 'text', array(
        //            'name'  => 'schedule',
        //            'label' => Mage::helper('bronto_reminder')->__('Schedule (Days)'),
        //            'note'  => '
        //                In what number of days to repeat reminder email, if the rule condition still matches. Enter days, comma-separated.<br/>
        //                <strong>Examples:</strong><br/>
        //                "<span style="font-family:monospace">0</span>": Message to be sent again the same day.<br/>
        //                "<span style="font-family:monospace">1</span>": Message to be sent again the next day.<br/>
        //            ',
        //        ));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getChooserConfig()
    {
        return array(
            'button' => array('open' => 'Select Rule...'),
            'type'   => 'adminhtml/promo_widget_chooser_rule'
        );
    }
}
