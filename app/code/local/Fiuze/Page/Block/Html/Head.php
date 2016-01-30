<?php
/**
 * Page
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     Page
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_Page_Block_Html_Head extends WIC_All_Block_Html_Head
{
    /**
     * Override to show product name as title on product pages
     *
     * @return string
     */
    public function getTitle()
    {
        if (Mage::registry('current_product')) {
            /** @var $product Mage_Catalog_Model_Product */
            $product = Mage::registry('current_product');
            if ($product instanceof Mage_Catalog_Model_Product && $product->getData('name')) {
                $title = htmlspecialchars(html_entity_decode(trim($product->getData('name')), ENT_QUOTES, 'UTF-8'));
                if (!empty($title)) {
                    return $title . ' - DailySale';
                }
            }
        }

        return parent::getTitle();
    }
}
