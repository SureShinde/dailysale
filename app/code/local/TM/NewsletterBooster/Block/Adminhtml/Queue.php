<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        $this->setTemplate('newsletterbooster/queue/list.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->setChild('grid', 
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_queue_grid', 'newsletterbooster.queue.grid'
            )
        );
        
        return parent::_beforeToHtml();
    }
}
