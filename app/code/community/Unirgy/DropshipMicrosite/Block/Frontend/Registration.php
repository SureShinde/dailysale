<?php

class Unirgy_DropshipMicrosite_Block_Frontend_Registration extends Mage_Directory_Block_Data
{
    public function getCountryHtmlSelect($defValue=null, $name='country_id', $id='country', $title='Country')
    {
        Varien_Profiler::start('TEST: '.__METHOD__);
        $_isQuickRegister = Mage::getStoreConfig('udropship/microsite/allow_quick_register');
        if (is_null($defValue)) {
            $defValue = $_isQuickRegister ? '' : $this->getCountryId();
        }
        $cacheKey = 'DIRECTORY_COUNTRY_SELECT_STORE_'.Mage::app()->getStore()->getCode();
        if (Mage::app()->useCache('config') && $cache = Mage::app()->loadCache($cacheKey)) {
            $options = unserialize($cache);
        } else {
            $options = $this->getCountryCollection()->toOptionArray();
            if (Mage::app()->useCache('config')) {
                Mage::app()->saveCache(serialize($options), $cacheKey, array('config'));
            }
        }
        $html = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setTitle(Mage::helper('udropship')->__($title))
            ->setClass($_isQuickRegister ? '' : 'validate-select')
            ->setValue($defValue)
            ->setOptions($options)
            ->getHtml();

        Varien_Profiler::stop('TEST: '.__METHOD__);
        return $html;
    }
    protected $_tplVendor;
    protected function _initTplVendor()
    {
        if (null === $this->_tplVendor) {
            $this->_tplVendor = Mage::getModel('udropship/vendor')->load(Mage::getStoreConfig('udropship/microsite/template_vendor'));
        }
        return $this;
    }
    public function getDefPreferedCarrier()
    {
        $this->_initTplVendor();
        return $this->_tplVendor->getCarrierCode();
    }
}