<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getLandingPageTitle($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $title = Mage::getStoreConfig('udropship/microsite/landing_page_title');
        if ($vendor->getData('landing_page_title')) {
            $title = $vendor->getData('landing_page_title');
        }
        $title = str_replace('[vendor_name]', $vendor->getVendorName(), $title);
        return !empty($title) ? $title : $vendor->getVendorName();
    }
    public function getLandingPageKeywords($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $keywords = Mage::getStoreConfig('udropship/microsite/landing_meta_keywords');
        if ($vendor->getData('landing_meta_keywords')) {
            $keywords = $vendor->getData('landing_meta_keywords');
        }
        $keywords = str_replace('[vendor_name]', $vendor->getVendorName(), $keywords);
        return !empty($keywords) ? $keywords : $vendor->getVendorName();
    }
    public function getLandingPageDescription($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $description = Mage::getStoreConfig('udropship/microsite/landing_meta_description');
        if ($vendor->getData('landing_meta_description')) {
            $description = $vendor->getData('landing_meta_description');
        }
        $description = str_replace('[vendor_name]', $vendor->getVendorName(), $description);
        return !empty($description) ? $description : $vendor->getVendorName();
    }

    public function isCurrentVendorFromProduct()
    {
        return Mage::helper('umicrosite/protected')->isCurrentVendorFromProduct();
    }
    public function resetCurrentVendor()
    {
        Mage::helper('umicrosite/protected')->resetCurrentVendor();
        return $this;
    }

    public function checkPermission($action, $vendor=null)
    {
        return Mage::helper('umicrosite/protected')->checkPermission($action, $vendor);
    }

    public function isAllowedAction($action, $vendor=null)
    {
        return Mage::helper('umicrosite/protected')->isAllowedAction($action, $vendor);
    }

    public function getCurrentVendor()
    {
        return Mage::helper('umicrosite/protected')->getCurrentVendor();
    }

    public function getUrlFrontendVendor($url)
    {
        return Mage::helper('umicrosite/protected')->getUrlFrontendVendor($url);
    }
    public function getFrontendVendor()
    {
        return Mage::helper('umicrosite/protected')->getFrontendVendor();
    }

    public function getAdminhtmlVendor()
    {
        return Mage::helper('umicrosite/protected')->getAdminhtmlVendor();
    }

    public function getManageProductsUrl()
    {
        $params = array();
        $hlp = Mage::getSingleton('adminhtml/url');
        if ($hlp->useSecretKey()) {
            $params[Mage_Adminhtml_Model_Url::SECRET_KEY_PARAM_NAME] = $hlp->getSecretKey();
        }
        return $hlp->getUrl('adminhtml/catalog_product', $params);
    }

    public function getCurrentVendorBaseUrl()
    {
        return Mage::helper('umicrosite/protected')->getVendorBaseUrl();
    }

    protected $_updateStoreBaseUrl;
    public function setCurUpdateStoreBaseUrl($ubu)
    {
        $oldUbu = $this->_updateStoreBaseUrl;
        $this->_updateStoreBaseUrl = $ubu;
        return $oldUbu;
    }
    public function getCurUpdateStoreBaseUrl($vendor=null)
    {
        $ubu = Mage::getStoreConfig('udropship/microsite/update_store_base_url');
        if ($this->_updateStoreBaseUrl!==null) {
            $ubu = $this->_updateStoreBaseUrl;
        }
        if ($vendor!==null && ($v=Mage::helper('udropship')->getVendor($vendor)) && $v->getId() && $v->getUpdateStoreBaseUrl()!=-1) {
            $ubu = $v->getUpdateStoreBaseUrl();
        }
        return $ubu;
    }
    public function getUpdateStoreBaseUrl($vendor=null)
    {
        $ubu = Mage::getStoreConfig('udropship/microsite/update_store_base_url');
        if ($vendor!==null && ($v=Mage::helper('udropship')->getVendor($vendor)) && $v->getId() && $v->getUpdateStoreBaseUrl()!=-1) {
            $ubu = $v->getUpdateStoreBaseUrl();
        }
        return $ubu;
    }

    protected $_subdomainLevel;
    public function setCurSubdomainLevel($sl)
    {
        $oldSl = $this->_subdomainLevel;
        $this->_subdomainLevel = $sl;
        return $oldSl;
    }
    public function getCurSubdomainLevel($vendor=null)
    {
        $sl = Mage::getStoreConfig('udropship/microsite/subdomain_level');
        if ($this->_subdomainLevel!==null) {
            $sl = $this->_subdomainLevel;
        }
        if ($vendor!==null && ($v=Mage::helper('udropship')->getVendor($vendor)) && $v->getId() && $v->getSubdomainLevel()) {
            $sl = $v->getSubdomainLevel();
        }
        return $sl;
    }
    public function getSubdomainLevel($vendor=null)
    {
        $sl = Mage::getStoreConfig('udropship/microsite/subdomain_level');
        if ($vendor!==null && ($v=Mage::helper('udropship')->getVendor($vendor)) && $v->getId() && $v->getSubdomainLevel()) {
            $sl = $v->getSubdomainLevel();
        }
        return $sl;
    }

    public function getVendorBaseUrl($vendor=null)
    {
        return Mage::helper('umicrosite/protected')->getVendorBaseUrl($vendor);
    }

    public function withOrigBaseUrl($url, $prefix='')
    {
        return Mage::helper('umicrosite/protected')->withOrigBaseUrl($url, $prefix);
    }

    public function updateStoreBaseUrl()
    {
        return Mage::helper('umicrosite/protected')->updateStoreBaseUrl();
    }

    /**
    * Get URL specific for vendor
    *
    * @param boolean|integer|Unirgy_Dropship_Model_Vendor $vendor
    * @param string|Mage_Catalog_Model_Product $orig original product or URL to be converted to vendor specific
    */
    public function getVendorUrl($vendor, $origUrl=null)
    {
        return Mage::helper('umicrosite/protected')->getVendorUrl($vendor, $origUrl);
    }

    public function getProductUrl($product)
    {
        return $this->getVendorUrl(Mage::helper('udropship')->getVendor($product), $product);
    }

    public function getVendorRegisterUrl()
    {
        return Mage::getUrl('umicrosite/vendor/register');
    }

    public function sendVendorSignupEmail($registration)
    {
        $store = Mage::app()->getDefaultStoreView();
        Mage::helper('udropship')->setDesignStore($store);
        Mage::getModel('core/email_template')->sendTransactional(
            $store->getConfig('udropship/microsite/signup_template'),
            $store->getConfig('udropship/vendor/vendor_email_identity'),
            $registration->getEmail(),
            $registration->getVendorName(),
            array(
                'store_name' => $store->getName(),
                'vendor' => $registration,
            )
        );
        Mage::helper('udropship')->setDesignStore();

        return $this;
    }

    public function sendVendorWelcomeEmail($vendor)
    {
        $store = Mage::app()->getDefaultStoreView();
        Mage::helper('udropship')->setDesignStore($store);
        Mage::getModel('core/email_template')->sendTransactional(
            $store->getConfig('udropship/microsite/welcome_template'),
            $store->getConfig('udropship/vendor/vendor_email_identity'),
            $vendor->getEmail(),
            $vendor->getVendorName(),
            array(
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            )
        );
        Mage::helper('udropship')->setDesignStore();

        return $this;
    }

    public function getDomainName()
    {
        $level = Mage::helper('umicrosite')->getSubdomainLevel();
        if (!$level) {
            return '';
        }
        $baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $url = parse_url($baseUrl);
        $hostArr = explode('.', $url['host']);
        return join('.', array_slice($hostArr, -($level-1)));
    }

    /**
    * Send new registration to store owner
    *
    * @param Mage_Sales_Model_Order_Shipment $shipment
    * @param string $comment
    */
    public function sendVendorRegistration($registration)
    {
        $store = Mage::app()->getStore($registration->getStoreId());
        $to = $store->getConfig('udropship/microsite/registration_receiver');
        $subject = $store->getConfig('udropship/microsite/registration_subject');
        $template = $store->getConfig('udropship/microsite/registration_template');
        $ahlp = Mage::getModel('adminhtml/url');

        if ($to && $subject && $template) {
            $data = $registration->getData();
            $data['store_name'] = $store->getName();
            $data['registration_url'] = $ahlp->getUrl('adminhtml/umicrositeadmin_registration/edit', array(
                'reg_id' => $registration->getId(),
                'key' => null,
                '_store'    => 0
            ));
            $data['all_registrations_url'] = $ahlp->getUrl('adminhtml/umicrositeadmin_registration', array(
                'key' => null,
                '_store'    => 0
            ));

            foreach ($data as $k=>$v) {
                $_v = is_array($v) ? implode(', ', $v) : $v;
                $subject = str_replace('{{'.$k.'}}', $_v, $subject);
                $template = str_replace('{{'.$k.'}}', $_v, $template);
            }

            foreach (explode(',', $to) as $toEmail) {
                Mage::getModel('core/email')
                    ->setFromEmail($registration->getEmail())
                    ->setFromName($registration->getVendorName())
                    ->setToEmail($toEmail)
                    ->setToName('')
                    ->setSubject($subject)
                    ->setBody($template)
                    ->send();
            }
        }

        return $this;
    }

    public function addVendorFilterToProductCollection($collection)
    {
        $vendor = $this->getCurrentVendor();

        if (Mage::helper('umicrosite')->isCurrentVendorFromProduct()) {
            $vendor = false;
        }

        try {
            if ($vendor) {
                if (!$collection->getFlag('udropship_vendor_joined')) {
                    $joinCond = '{{table}}.vendor_id='.intval($vendor->getId());
                    if (!Mage::getStoreConfigFlag('udropship/microsite/front_show_all_products')) {
                        $joinCond .= ' and {{table}}.is_attribute=1';
                    }
                    $collection->joinTable(
                        'udropship/vendor_product_assoc', 'product_id=entity_id',
                        array('microsite_vendor'=>'vendor_id'),
                        $joinCond
                    );
                    $collection->setFlag('udropship_vendor_joined',1);
                }
            } else {
                $cond = "{{table}}.vendor_id IS null OR {{table}}.status='A'";
                $session = Mage::getSingleton('udropship/session');
                if ($session->isLoggedIn() && $session->getVendor()->getStatus()=='I') {
                    $cond .= " OR {{table}}.vendor_id=".$session->getVendor()->getId();
                }
                $alreadyJoined = false;
                foreach ($collection->getSelect()->getPart(Zend_Db_Select::COLUMNS) as $column) {
                    if ($column[2]=='udropship_vendor' || $column[2]=='udropship_status') {
                        $alreadyJoined = true;
                        break;
                    }
                }
                if (!$alreadyJoined) {
                    $collection->joinAttribute('udropship_vendor', 'catalog_product/udropship_vendor', 'entity_id', null, 'left');
                    $collection->joinField('udropship_status', 'udropship/vendor', 'status', 'vendor_id=udropship_vendor', $cond, 'left');
                }
            }
        } catch (Exception $e) {
            $skip = array(
                Mage::helper('udropship')->__('Joined field with this alias is already declared'),
                Mage::helper('udropship')->__('Invalid alias, already exists in joined attributes'),
                Mage::helper('udropship')->__('Invalid alias, already exists in joint attributes.'),
            );
            if (!in_array($e->getMessage(), $skip)) {
                throw $e;
            }
        }
        return $this;
    }
    protected $_vendorCatIds;
    public function getVendorCategoryIds()
    {
        if (is_null($this->_vendorCatIds)) {
            $this->_vendorCatIds = array();
            if (($v = $this->getCurrentVendor()) && $v->getIsLimitCategories()) {
                $this->_vendorCatIds = explode(',', implode(',', (array)$v->getLimitCategories()));
            }
        }
        return $this->_vendorCatIds;
    }
    public function getVendorEnableCategories()
    {
        $v = $this->getCurrentVendor();
        if ($v && $v->getIsLimitCategories() == 1) {
            return $this->getVendorCategoryIds();
        } else {
            return false;
        }
    }
    public function getVendorDisableCategories()
    {
        $v = $this->getCurrentVendor();
        if ($v && $v->getIsLimitCategories() == 2) {
            return $this->getVendorCategoryIds();
        } else {
            return false;
        }
    }
    public function useVendorCategoriesFilter()
    {
        return ($v = $this->getCurrentVendor()) && $v->getIsLimitCategories() && !Mage::app()->getStore()->isAdmin() && !$this->isCurrentVendorFromProduct();
    }

}
