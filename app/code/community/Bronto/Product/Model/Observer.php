<?php

class Bronto_Product_Model_Observer
{
    /**
     * Cron job to process all content tags for the product recommendations
     */
    public function processContentTags()
    {
        $results = array(
            'total' => 0,
            'success' => 0,
            'error' => 0
        );
        $stores = Mage::app()->getStores(true);
        foreach ($stores as $store) {
            $storeResults = $this->processContentTagsForStore($store);
            $results['total'] += $storeResults['total'];
            $results['success'] += $storeResults['success'];
            $results['error'] += $storeResults['error'];
        }
        return $results;
    }

    /**
     * Process the content tags for a given scope
     *
     * @return array
     */
    public function processContentTagsForScope()
    {
        $scopeParams = Mage::helper('bronto_product')->getScopeParams();
        if ($scopeParams['store']) {
            return $this->processContentTagsForStore($scopeParams['store_id']);
        } else if ($scopeParams['website']) {
            return $this->processContentTagsForSite($scopeParams['website_id']);
        } else {
            return $this->processContentTags();
        }
    }

    /**
     * Process the content tags for a given website
     *
     * @param int $websiteId
     * @return array
     */
    public function processContentTagsForSite($websiteId)
    {
        $results = array(
            'total' => 0,
            'success' => 0,
            'error' => 0,
        );

        $website = Mage::app()->getWebsite($websiteId);
        foreach ($website->getStores() as $store) {
            foreach ($this->processContentTagsForStore($store) as $k => $v) {
                $results[$k] += $v;
            }
        }
        return $results;
    }

    /**
     * Process content tags for a single store view
     *
     * @param mixed $storeOrId
     * @return array
     */
    public function processContentTagsForStore($storeOrId)
    {
        $results = array(
            'total' => 0,
            'success' => 0,
            'error' => 0,
        );

        $helper = Mage::helper('bronto_product');
        $store = $storeOrId;
        if (is_numeric($storeOrId)) {
            $store = Mage::app()->getStore($storeOrId);
        }

        $recTags = Mage::getModel('bronto_product/recommendation')
            ->getCollection()
            ->onlyContentTagBased()
            ->addStoreToFilter($store->getId());

        // Cron or no cron... it must be enabled
        if (!$helper->isEnabled('store', $store->getId())) {
            continue;
        }

        $api = $helper->getApi(null, 'store', $store->getId());
        $tagObject = $api->getContentTagObject();
        foreach ($recTags as $recTag) {
            $results['total']++;
            try {
                $tag = $helper->getContentTagForRecommendation($tagObject, $recTag);
                $tag->value = $helper->processTagContent($recTag, $store->getId());
                $tag->save();
                $recTag
                    ->setTagId($tag->id)
                    ->save();
                $results['success']++;
            } catch (Exception $e) {
                $message = $e->getMessage();
                if ($e->getCode() == 1604) {
                    $message = 'Message content appears to be formatted incorrectly. Make sure the API tags are surrounded by {dynamic_code}{loop} blocks.';
                }
                $helper->writeError("Unable to save content tag for {$recTag->getName()} in store {$store->getId()}: {$message}");
                $results['error']++;
            }
        }

        return $results;
    }
}
