<?php

class Unirgy_DropshipMicrosite_Model_Store extends Mage_Core_Model_Store
{
    protected $_oldUseVendorUrl=true;
    protected $_useVendorUrl=null;
    public function useVendorUrl($flag=null)
    {
        $result = $this->_useVendorUrl;
        if (!is_null($flag) && $this->_useVendorUrl!==$flag) {
            $this->_oldUseVendorUrl = $this->_useVendorUrl;
            $this->_useVendorUrl = $flag;
        }
        return $result;
    }
    public function resetUseVendorUrl()
    {
        $this->_useVendorUrl = $this->_oldUseVendorUrl;
        return $this;
    }
    protected $_curMySecure;
    public function getBaseUrl($type=self::URL_TYPE_LINK, $secure=null)
    {
        $this->_curMySecure = $secure;
        $cacheKey = $type.'/'.(is_null($secure) ? 'null' : ($secure ? 'true' : 'false'));
        if ($this->_useVendorUrl === true) {
            $cacheKey .= 'true';
        } elseif ($this->_useVendorUrl === false) {
            $cacheKey .= 'false';
        } else {
            $cacheKey .= $this->_useVendorUrl;
        }
        if (!isset($this->_baseUrlCache[$cacheKey])) {
            switch ($type) {
                case self::URL_TYPE_WEB:
                    $secure = is_null($secure) ? $this->isCurrentlySecure() : (bool)$secure;
                    $url = $this->getConfig('web/'.($secure ? 'secure' : 'unsecure').'/base_url');
                    break;

                case self::URL_TYPE_LINK:
                    $secure = (bool)$secure;
                    $url = $this->getConfig('web/'.($secure ? 'secure' : 'unsecure').'/base_link_url');
                    $url = $this->_updatePathUseRewrites($url);
                    $url = $this->_updatePathUseStoreView($url);
                    break;

                case self::URL_TYPE_DIRECT_LINK:
                    $secure = (bool)$secure;
                    $url = $this->getConfig('web/'.($secure ? 'secure' : 'unsecure').'/base_link_url');
                    $url = $this->_updatePathUseRewrites($url);
                    break;

                case self::URL_TYPE_SKIN:
                case self::URL_TYPE_MEDIA:
                case self::URL_TYPE_JS:
                    $secure = is_null($secure) ? $this->isCurrentlySecure() : (bool)$secure;
                    $url = $this->getConfig('web/'.($secure ? 'secure' : 'unsecure').'/base_'.$type.'_url');
                    break;

                default:
                    throw Mage::exception('Mage_Core', Mage::helper('udropship')->__('Invalid base url type'));
            }
            
            if (false !== strpos($url, '{{base_url}}')) {
                $baseUrl = Mage::getConfig()->substDistroServerVars('{{base_url}}');
                $url = str_replace('{{base_url}}', $baseUrl, $url);
            }

            $this->_baseUrlCache[$cacheKey] = rtrim($url, '/').'/';
        }
        return $this->_baseUrlCache[$cacheKey];
    }
    protected function _updatePathUseStoreView($url)
    {
        $msHlp = Mage::helper('umicrosite');
        $baseCheck = Mage::isInstalled() && !$this->isAdmin() && !$this->_curMySecure;
        if ($baseCheck && $this->_useVendorUrl !== false) {
            if ($this->_useVendorUrl === true
                || $this->_useVendorUrl === null && $msHlp->getCurUpdateStoreBaseUrl()
            ) {
                $vendor = Mage::helper('umicrosite')->getCurrentVendor();
            } else {
                $vendor = $this->_useVendorUrl;
            }
            if ($vendor
                && ($vendor = Mage::helper('udropship')->getVendor($vendor))
                && $vendor->getId()
            ) {
                if (1 == Mage::helper('umicrosite')->getCurSubdomainLevel($vendor)) {
                    $url .= $vendor->getUrlKey().'/';
                } elseif (!$msHlp->getUpdateStoreBaseUrl($vendor)
                    || !Mage::helper('umicrosite')->getFrontendVendor()
                    || !Mage::helper('umicrosite')->getFrontendVendor()->getId()==$vendor->getId()
                ) {
                    $url = Mage::helper('umicrosite')->getVendorBaseUrl($vendor);
                }
            }
        }
        return parent::_updatePathUseStoreView($url);
    }
}