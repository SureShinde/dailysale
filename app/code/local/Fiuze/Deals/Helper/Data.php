<?php

/**
 * Data Helper
 *
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Helper_Data extends Mage_Core_Helper_Abstract{

    const XML_ROOT = 'fiuze_deals_cron_time/fiuze_deals_cron_time_grp';

    protected $_rootConfig = null;

    /**
     * Set root config
     */
    public function __construct(){
        $this->_rootConfig = new Varien_Object(Mage::getStoreConfig(self::XML_ROOT));
    }

    /**
     * Check if deals rotation extension is enabled
     *
     * @return bool
     */
    public function isEnabled(){
        return $this->_extensionEnabled('Fiuze_Deals');
    }

    /**
     * Retrieve root layout template name
     *
     * @return string
     */
    public function getLayout(){
        return $this->_rootConfig->getData('layout');
    }

    /**
     * Retrieve cron scheduler
     *
     * @return string
     */
    public function getScheduler(){
        return $this->_rootConfig->getData('scheduler');
    }

    /**
     * Retrieve route name from system configuration
     *
     * @return string
     */
    public function getRoute(){
        return $this->_rootConfig->getRoute();
    }

    /**
     * Retrieve full route url
     *
     * @param null $store
     * @return string
     */
    public function getRouteUrl($store = null){
        return Mage::getUrl($this->getRoute(), array('_store' => $store));

    }

    /**
     * Retrieve current product model (in cron set)
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProductCron(){
        if(!Mage::registry('product')){

            $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('current_active', 1)
                ->getFirstItem();

            try{
                $currentProduct = Mage::getModel('catalog/product')->load($productActive->getProductId());
                Mage::register('product', $currentProduct);
                Mage::register('current_product', $currentProduct);
            } catch(Exception $e){
                Mage::logException($e);
            }
        }
        return Mage::registry('product');
    }

    /**
     * Retrieve current category model (in cron set)
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategoryCron(){
        if(!Mage::registry('category_cron')){
            $category = Mage::getModel('catalog/category')->load((int)$this->_rootConfig->getCategory());
            Mage::register('category_cron', $category);
        }

        return Mage::registry('category_cron');
    }

    /**
     * Retrieve time cron
     *
     * @return
     */
    public function getTimeCron(){
        if(!Mage::registry('time_cron')){
            $timeCron = $this->_rootConfig->getCronTime();
            Mage::register('time_cron', $timeCron);
        }
        return Mage::registry('time_cron');
    }

    /**
     * Retrieve cron expression
     *
     * @return string
     */
    public function getCronExpr(){
        list($hh, $mm, $ss) = explode(',', $this->getTimeCron());
        $mm += $hh * 60;

        $cronExprArray = array(
            intval($mm) ? '/' . intval($mm) : '*', # Minute
            '*', # Hour
            '*', # Day of the Month
            '*', # Month of the Year
            '*', # Day of the Week
        );
        $cronExprString = join(' ', $cronExprArray);
        $cronExprString = '*' . $cronExprString;

        return $cronExprString;
    }

    /**
     * Retrieve end time for deal product in the frontend
     *
     * @return Zend_Date
     */
    public function getEndDealTime(){
        list($hh, $mm, $ss) = explode(",", $this->getTimeCron());
        $mm += $hh * 60;
        $date = new Zend_Date();
        $date->add($mm, Zend_Date::MINUTE);

        return $date;
    }

    /**
     * Check if deals rotation extension is available
     *
     * @param string $extensionName
     * @return bool
     */
    protected function _extensionEnabled($extensionName){
        $modules = (array)Mage::getConfig()->getNode('modules')->children();
        if(
            !isset($modules[$extensionName])
            || $modules[$extensionName]->descend('active')->asArray() == 'false'
            || Mage::getStoreConfig('advanced/modules_disable_output/' . $extensionName)
        ){
            return false;
        }

        return true;
    }

    /**
     * Get options in Quantity html
     *
     * @param  int
     * @return String
     */
    public function toQuantityHtml($qty){
        if((int)$qty){
            return '';
        } else{
            return '<p class="note" style="color: #d81010">' . $this->__('Product qty should be greater than 0.') . '</p>';
        }
    }
}