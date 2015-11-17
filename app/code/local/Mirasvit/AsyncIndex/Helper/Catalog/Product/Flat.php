<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Fast Asynchronous Re-indexing
 * @version   1.1.6
 * @build     363
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


/**
 * Переопределяем дефолтный хелпер, для того что бы в любом случае использовалить flat таблицы (если они включены) (catalog_product_flat)
 * в оригинале, если индекс не валиден - работа идет с таблицами catalog_product_entity
 *
 * @category Mirasvit
 * @package  Mirasvit_AsyncIndex
 */
class Mirasvit_AsyncIndex_Helper_Catalog_Product_Flat extends Mage_Catalog_Helper_Product_Flat
{
    public function isEnabled($store = null)
    {
        if (version_compare(Mage::getVersion(), '1.8.0.0', '>=')) {
            return parent::isEnabled($store);
        }

        $store = Mage::app()->getStore($store);
        if ($store->isAdmin()) {
            return false;
        }

        if (!isset($this->_isEnabled[$store->getId()])) {
            if (Mage::getStoreConfigFlag(self::XML_PATH_USE_PRODUCT_FLAT, $store)) {
                $this->_isEnabled[$store->getId()] = true;
            } else {
                $this->_isEnabled[$store->getId()] = false;
            }
        }

        return $this->_isEnabled[$store->getId()];
    }
}