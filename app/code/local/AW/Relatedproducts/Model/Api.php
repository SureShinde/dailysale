<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Relatedproducts
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Relatedproducts_Model_Api
{
    /**
     * @param int|array $productIds
     * @param int $storeId
     * @return array $productId => $count
     */
    public function getRelatedProductsFor($productIds, $storeId)
    {
        if (!is_array($productIds)) {
            $productIds = array(intval($productIds));
        }

        /** @var $helper AW_Relatedproducts_Helper_Data */
        $helper = Mage::helper('relatedproducts');
        foreach ($productIds as $productId) {
            if (!$helper->isInstalledForProduct($productId, $storeId)) {
                $helper->installForProduct($productId, $storeId);
            }
        }

        /** @var $relatedCollection AW_Relatedproducts_Model_Mysql4_Relatedproducts_Collection */
        $relatedCollection = Mage::getModel('relatedproducts/relatedproducts')
            ->getCollection()
            ->addProductFilter($productIds)
            ->addStoreFilter($storeId);

        $relatedIds = array();
        foreach ($relatedCollection as $item) {
            /** @var $item AW_Relatedproducts_Model_Relatedproducts */
            foreach ($item->getRelatedArray() as $id => $count) {
                if (array_key_exists($id, $relatedIds)) {
                    $relatedIds[$id] += $count;
                } else {
                    $relatedIds[$id] = $count;
                }
            }
        }

        /** Remove $productIds from $relatedIds and sort $relatedIds */
        $relatedIds = array_diff_key($relatedIds, array_flip($productIds));
        arsort($relatedIds);

        return $relatedIds;
    }

    /**
     * Returns value for "Show products from one category only" configuration option
     * @param mixed $storeId
     * @return mixed
     */
    public function getShowForCurrentCategory($storeId = null)
    {
        return Mage::helper('relatedproducts/config')->getGeneralSameCategory($storeId);
    }
}