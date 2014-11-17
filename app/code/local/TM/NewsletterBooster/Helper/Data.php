<?php

class TM_NewsletterBooster_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_TEMPLATE_FILTER = 'global/newsletter/tempate_filter';

    public function getTemplateProcessor()
    {
        $model = 'newsletterbooster/template_filter';

        return Mage::getModel($model);
    }

    public function getUnsubscribeUrl($campaign)
    {
        return Mage::getModel('core/url')
            ->getUrl('newsletterbooster/track/unsubscribe', array(
                'id'     => $campaign->getCampaignId(),
                'entity' => $campaign->getCustomerId(),
                'queue'  => $campaign->getQueueId()
            ));
    }

    public function getUnsubscribeCampaignUrl()
    {
        return $this->_getUrl('newsletterbooster/track/unsubscribepost')->getSecure();
    }
}
