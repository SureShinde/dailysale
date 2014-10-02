<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Tab_Queue
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        /* @var $queue Mage_Newsletter_Model_Queue */
        $queue = Mage::getSingleton('newsletterbooster/queue');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    =>  Mage::helper('newsletterbooster')->__('Queue Information'),
            'class'    =>  'fieldset-wide'
        ));

        $outputFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

        $fieldset->addField('queue_title', 'text', array(
            'label'     => Mage::helper('newsletterbooster')->__('Queue Title'),
            'name'      => 'queue_title',
        ));

        if($queue->getQueueStatus() == TM_NewsletterBooster_Model_Queue::STATUS_NEVER) {

            $fieldset->addField('date', 'date',array(
                'name'      =>    'start_at',
                'time'      =>    true,
                'format'    =>    $outputFormat,
                'label'     =>    Mage::helper('newsletterbooster')->__('Queue Start'),
                'image'     =>    $this->getSkinUrl('images/grid-cal.gif')
            ));
        } else {
            $fieldset->addField('date','date',array(
                'name'      => 'start_at',
                'time'      => true,
                'disabled'  => 'true',
                'style'     => 'width:38%;',
                'format'    => $outputFormat,
                'label'     => Mage::helper('newsletterbooster')->__('Queue Start'),
                'image'     => $this->getSkinUrl('images/grid-cal.gif')
            ));
        }

        $fieldset->addField('campaign_id', 'hidden', array(
            'name' => 'campaign_id',
        ));

        $fieldset->addField('queue_id', 'hidden', array(
            'name' => 'queue_id',
        ));

        if ($queue->getQueueStartAt()) {
            $form->getElement('date')->setValue(
                Mage::app()->getLocale()->date($queue->getQueueStartAt(), Varien_Date::DATETIME_INTERNAL_FORMAT)
            );
        }
        $form->getElement('queue_title')->setValue($this->getFormCampaignValue());
        if ($this->getRequest()->getParam('campaign')) {
            $form->getElement('campaign_id')->setValue($this->getRequest()->getParam('campaign'));
        } else {
            $form->getElement('campaign_id')->setValue(Mage::registry('tm_current_queue')->getCampaignId());
            $form->getElement('queue_id')->setValue(Mage::registry('tm_current_queue')->getQueueId());
        }

        $this->setForm($form);

        return $this;
    }

    public function getFormCampaignValue()
    {
        if ($this->getRequest()->getParam('campaign')) {
            $model = Mage::getModel('newsletterbooster/campaign');
            $model->load($this->getRequest()->getParam('campaign'));
            return $model->getTemplateCode();
        } else {
            return Mage::registry('tm_current_queue')->getQueueTitle();
        }
    }
}
