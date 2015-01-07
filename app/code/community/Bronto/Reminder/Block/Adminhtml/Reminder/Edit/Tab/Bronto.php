<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_Bronto extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare general properties form
     *
     * @return Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_Bronto
     */
    protected function _prepareForm()
    {
        $form  = new Varien_Data_Form();
        $model = Mage::registry('current_reminder_rule');

        $fieldset = $form->addFieldset('message_fieldset', array(
            'legend'      => Mage::helper('bronto_reminder')->__('Bronto Messages'),
            'table_class' => 'form-list stores-tree',
            'comment'     => Mage::helper('bronto_reminder')->__('Messages will be sent only for specified store views.'),
        ));

        $sendOptions = Mage::getModel('bronto_common/system_config_source_sendOptions');

        foreach (Mage::app()->getWebsites() as $website) {
            $groups = $website->getGroups();

            if (count($groups) == 0) {
                continue;
            }
            $fieldset->addField("website_message_{$website->getId()}", 'note', array(
                'label'               => $website->getName(),
                'fieldset_html_class' => 'website',
            ));
            foreach ($website->getGroups() as $gkey => $group) {
                $stores = $group->getStores();

                if (count($stores) == 0) {
                    continue;
                }
                $fieldset->addField("group_message_{$group->getId()}", 'note', array(
                    'label'               => $group->getName(),
                    'fieldset_html_class' => 'store-group',
                ));
                foreach ($stores as $key => $store) {
                    if (Mage::helper('bronto_reminder')->isEnabled('store', $store->getId())) {
                        $values = Mage::helper('bronto_reminder/message')->getMessagesOptionsArray($store->getId(), $website->getId());
                        $disabled = count($values) == 1;

                        $fieldset->addField("store_message_{$store->getId()}", 'select', array(
                            'name'                => "store_messages[{$store->getId()}]",
                            'required'            => false,
                            'label'               => $store->getName(),
                            'values'              => $values,
                            'fieldset_html_class' => 'store',
                            'disabled'            => $disabled,
                        ));

                        $fieldset->addField("store_message_sendflags_{$store->getId()}", 'select', array(
                            'name' => "store_message_sendflags[{$store->getId()}]",
                            'required' => false,
                            'label' => "Send Flags",
                            'fieldset_html_class' => 'store',
                            'values' => $sendOptions->toOptionArray(),
                            'disabled' => $disabled
                        ));

                        $fieldset->addField("store_message_sendtype_{$store->getId()}", 'radios', array(
                            'name'                => "store_message_sendtypes[{$store->getId()}]",
                            'required'            => false,
                            'label'               => '',
                            'value'               => 'transactional',
                            'values'              => array(
                                array('value' => 'transactional', 'label' => ' Send as Transactional'),
                                array('value' => 'marketing', 'label' => ' Send as Marketing'),
                            ),
                            'fieldset_html_class' => 'store',
                            'disabled'            => $disabled
                        ));

                        //                        $fieldset->addField("store_message_salesrule_id_{$store->getId()}", 'select', array(
                        //                            'name'      => "store_message_salesrule_ids[{$store->getId()}]",
                        //                            'required'  => false,
                        //                            'label'     => 'Sales Rule Coupon',
                        //                            'values'    => Mage::helper('bronto_common/salesrule')->getRuleOptionsArray(),
                        //                            'fieldset_html_class' => 'store_child',
                        //                            'disabled'            => count($values) == 1 ? true : false,
                        //                        ));
                    } else {
                        unset($stores[$key]);
                    }
                }
                if (count($stores) == 0) {
                    unset($groups[$gkey]);
                    $fieldset->removeField("group_message_{$group->getId()}");
                }
            }

            if (count($groups) == 0) {
                $fieldset->removeField("website_message_{$website->getId()}");
            }
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
