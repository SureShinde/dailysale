<?php

class Turnto_Admin_Helper_Data extends Mage_Core_Helper_Data
{

    protected $_activated = null;
    protected $_feed = null;

    public function isFeedActivated()
    {
        $feed_url = $this->getFeedUrl();
        return $this->checkFeed($feed_url);
    }

    public function checkFeed($url = null)
    {

        if (!$url)
            return null;

        if (!$xml = simplexml_load_file($url)) {

            $active = false;
        } else {
            $this->_feed = simplexml_load_file($url);
            $active = true;
        }

        return $active;
    }

    public function getFeedUrl()
    {

        $url = Mage::getStoreConfig('turnto_admin/feedconfig/url');
        $site_key = Mage::getStoreConfig('turnto_admin/feedconfig/site_key');
        $site_auth = Mage::getStoreConfig('turnto_admin/feedconfig/site_auth');

        if (!$url || !$site_key || !$site_auth)
            return null;

        $feed_url = rtrim($url, '/') . '/' . $site_key . '/' . $site_auth . '/turnto-skuaveragerating.xml';

        return $feed_url;
    }


    public function loadRatings($url = null)
    {

        if (!$this->_feed) {

            if (!$url)
                $url = $this->getFeedUrl();

            if (!$this->checkFeed($url))
                return null;
        }


        $key_field = 'sku';

        foreach ($this->_feed->products->product as $prod_rating) {

            if (!$prod_rating[$key_field]) {
                Mage::log('Product sku (id) is missing in the feed. Feed product record is invalid...', null, 'turnto_product_ratings.log');
                continue;
            }


            $rating = Mage::getModel('turnto_admin/rating')
                ->getCollection()
                //    ->addFieldToFilter('product_id', $prod_rating[$key_field])
                ->addFieldToFilter($key_field, $prod_rating[$key_field])
                ->getFirstItem();

            try {


                # udpate bv_average_rating attribute

                $bvProductExternalId = $prod_rating[$key_field];

                $productAverageRating = (string)$prod_rating;
                //     echo $productAverageRating; echo '<br>';
                $productReviewCount = $prod_rating['review_count'];
                $productRatingRange = 5;

                // $product = Bazaarvoice_Helper_Data::getProductFromProductExternalId($bvProductExternalId);
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $bvProductExternalId);

                if ($product) {

                    $productAverageRating = (int)round($productAverageRating);

                    $attribute = Mage::getModel('catalog/product')->getResource()->getAttribute("bv_average_rating");

                    if ($attribute) {

                        $productAverageRating = $attribute->getSource()->getOptionId($productAverageRating);
                        $product->setBvAverageRating($productAverageRating);
                        $product->setBvReviewCount($productReviewCount);
                        $product->setBvRatingRange($productRatingRange);
                        $product->getResource()->saveAttribute($product, 'bv_average_rating');
                        $product->getResource()->saveAttribute($product, 'bv_review_count');
                        $product->getResource()->saveAttribute($product, 'bv_rating_range');
                    }

                    # save rating for tne product
                    if (!$rating->getCreatedAt())
                        $rating->setCreatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'));

                    $rating->setSku($prod_rating[$key_field])
                        ->setProductId($product->getId())
                        ->setReviewCount($prod_rating['review_count'])
                        ->setUpdatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'))
                        ->setRating((string)$prod_rating)
                        ->save();


                } else {
                    Mage::log('Could not find Magento product for xml file product id:' . $bvProductExternalId, null, 'turnto_product_ratings.log');
                }

            } catch (Exception $e) {
                Mage::log('Error updating product rating for product id:' . $prod_rating[$key_field] . '. Error details:' . $e->getMessage(), null, 'turnto_product_ratings.log');

            }

        }

        #cleanup turnto_product_ratings table
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');

        $table_name = Mage::getConfig()->getTablePrefix() . 'turnto_products_ratings';

        $sql = "DELETE FROM " . $table_name . " WHERE sku = ''";

        try {
            $result = $write->query($sql);

        } catch (Exception $e) {
            Mage::log('Error:' . $e->getMessage(), null, 'turnto_product_ratings.log');
        }

    }

    public function getLastUpdated()
    {

        $updated = null;

        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');

        $query = "SELECT MAX(updated_at) as updated FROM " . Mage::getSingleton('core/resource')->getTableName('turnto_admin/rating');

        $result = $read->fetchAll($query);

        if ($result[0]['updated'])
            $updated = $result[0]['updated'];

        // var_dump($query); var_dump($result); exit;

        return $updated;
    }
}