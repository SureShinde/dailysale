<?php

/**
 * Data Helper
 *
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_ROOT = 'fiuze_deals_cron_time/fiuze_deals_cron_time_grp';
    const DEFAULT_ROOT = 'blowout';
    const XML_PATH_LAYOUT = 'fiuze_deals_cron_time/fiuze_deals_cron_time_grp/layout';

    public function getRoute($store = null)
    {
        $config = new Varien_Object($this->getConf(self::XML_ROOT, $store));
        $route = strtolower(trim($config->getData('route')));
        if (!$route) {
            $route = self::DEFAULT_ROOT;
        }
        return $route;
    }

    public function getRouteUrl($store = null)
    {
        return Mage::getUrl($this->getRoute($store), array('_store' => $store));

    }

    public function ifStoreChangedRedirect()
    {
        $path = Mage::app()->getRequest()->getPathInfo();

        $helper = Mage::helper('fiuze_deals');
        $currentRoute = $helper->getRoute();

        $fromStore = Mage::app()->getRequest()->getParam('___from_store');
        if ($fromStore) {
            $fromStoreId = $helper->getStoreIdByCode($fromStore);
            $fromRoute = $helper->getRoute($fromStoreId);

            $url = preg_replace("#$fromRoute#si", $currentRoute, $path, 1);
            $url = Mage::getBaseUrl() . ltrim($url, '/');

            Mage::app()->getFrontController()->getResponse()
                ->setRedirect($url)
                ->sendResponse();
            exit;
        }
    }

    public function getEnabled()
    {
        return $this->extensionEnabled('Fiuze_Deals');
    }

    public function extensionEnabled($extensionName)
    {
        $modules = (array)Mage::getConfig()->getNode('modules')->children();
        if (
            !isset($modules[$extensionName])
            || $modules[$extensionName]->descend('active')->asArray() == 'false'
            || Mage::getStoreConfig('advanced/modules_disable_output/' . $extensionName)
        ) {
            return false;
        }
        return true;
    }

    public function getLayout($store = null)
    {
        return $this->getConf(self::XML_PATH_LAYOUT, $store);
    }

    /**
     * Retrieve current config model
     * @param string $path
     * @param mixed $store
     * @return public
     */
    private function getConf($code, $store = null)
    {
        return Mage::getStoreConfig($code, $store);
    }

    /**
     * Retrieve current product model (in cron set)
     ****************************************************************-----------------------------------------
     * @return Mage_Catalog_Model_Product
     */
    public function getProductCron()
    {
        if (!Mage::registry('product')) {

            $config = new Varien_Object($this->getConf(self::XML_ROOT));
            $categoryId = (int)$config->getData('category');
            $category = Mage::getModel('catalog/category')->load($categoryId);

            Mage::register('product', Mage::getModel('catalog/product')->load(1));
            Mage::register('current_product', Mage::getModel('catalog/product')->load(1));

        }
        return Mage::registry('product');
    }

    /**
     * Retrieve current category model (in cron set)
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategoryCron()
    {
        if (!Mage::registry('category_cron')) {
            $config = new Varien_Object($this->getConf(self::XML_ROOT));
            $categoryId = (int)$config->getData('category');
            Mage::register('category_cron', Mage::getModel('catalog/category')->load($categoryId));
        }
        return Mage::registry('category_cron');
    }
    /**
     * Retrieve time cron
     *
     * @return
     */
    public function getTimeCron()
    {
        if (!Mage::registry('time_cron')) {
            $config = new Varien_Object($this->getConf(self::XML_ROOT));
            $timeCron = $config->getData('cron_time');
            Mage::register('time_cron', $timeCron);
        }
        return Mage::registry('time_cron');
    }
}