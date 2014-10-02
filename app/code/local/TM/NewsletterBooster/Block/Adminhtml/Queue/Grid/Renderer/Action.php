<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    public function render(Varien_Object $row)
    {
        $actions = array();

        if($row->getQueueStatus()==TM_NewsletterBooster_Model_Queue::STATUS_NEVER) {
               if(!$row->getQueueStartAt()) {
                $actions[] = array(
                    'url' => $this->getUrl('*/*/start', array('id'=>$row->getId())),
                    'caption'	=> Mage::helper('newsletterbooster')->__('Start')
                );
            }
        } else if ($row->getQueueStatus()==TM_NewsletterBooster_Model_Queue::STATUS_SENDING) {
            $actions[] = array(
                    'url' => $this->getUrl('*/*/pause', array('id'=>$row->getId())),
                    'caption'	=>	Mage::helper('newsletterbooster')->__('Pause')
            );

            $actions[] = array(
                'url'		=>	$this->getUrl('*/*/cancel', array('id'=>$row->getId())),
                'confirm'	=>	Mage::helper('newsletterbooster')->__('Do you really want to cancel the queue?'),
                'caption'	=>	Mage::helper('newsletterbooster')->__('Cancel')
            );


        } else if ($row->getQueueStatus()==TM_NewsletterBooster_Model_Queue::STATUS_PAUSE) {

            $actions[] = array(
                'url' => $this->getUrl('*/*/resume', array('id'=>$row->getId())),
                'caption'	=>	Mage::helper('newsletterbooster')->__('Resume')
            );

        }

        $actions[] = array(
            'url'       =>  $this->getUrl('*/newsletterbooster_queue/preview',array('id'=>$row->getId())),
            'caption'   =>  Mage::helper('newsletterbooster')->__('Preview'),
            'popup'     =>  true
        );

        $this->getColumn()->setActions($actions);
        return parent::render($row);
    }
}
