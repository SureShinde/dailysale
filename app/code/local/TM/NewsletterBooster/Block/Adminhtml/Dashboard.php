<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Template
{
    protected $_locale;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('newsletterbooster/dashboard/index.phtml');

    }

    protected function _prepareLayout()
    {
        $this->setChild('lastCampaigns',
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_dashboard_last_campaigns'
            )
        );

        $this->setChild('campaign',
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_dashboard_campaign'
            )
        );

        $this->setChild('lastQueue',
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_dashboard_last_queue'
            )
        );

        $block = $this->getLayout()->createBlock(
            'newsletterbooster/adminhtml_dashboard_diagrams'
        );

        $this->setChild('diagrams', $block);

        $this->setChild('grids',
            $this->getLayout()->createBlock(
                'newsletterbooster/adminhtml_dashboard_grids'
            )
        );

        parent::_prepareLayout();
    }

    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }
        return $this->getUrl('*/*/*', array('_current'=>true, 'period'=>null));
    }
}
