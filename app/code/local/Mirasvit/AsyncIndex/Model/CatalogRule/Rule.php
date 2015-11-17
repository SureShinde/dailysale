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
 * Переопределяем дефолтную модель, для того что бы применять Catalog Rule только если есть параметр force
 * 
 * @category Mirasvit
 * @package  Mirasvit_AsyncIndex
 */
class Mirasvit_AsyncIndex_Model_CatalogRule_Rule extends Mage_CatalogRule_Model_Rule
{
    public function applyAllRulesToProduct($product, $force = false)
    {
        if ($force) {
            return parent::applyAllRulesToProduct($product);
        } else {
            return $this;
        }
    }
}
