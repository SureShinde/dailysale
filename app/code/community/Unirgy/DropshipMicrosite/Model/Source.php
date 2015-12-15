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

class Unirgy_DropshipMicrosite_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const AUTO_APPROVE_NO = 0;
    const AUTO_APPROVE_YES = 1;
    const AUTO_APPROVE_YES_ACTIVE = 2;

    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $hlpc = Mage::helper('umicrosite');

        switch ($this->getPath()) {

        case 'subdomain_level';
        case 'udropship/microsite/subdomain_level':
            $options = array(
                0 => Mage::helper('udropship')->__('Disable'),
                1 => Mage::helper('udropship')->__('From URL Path (domain.com/vendor)'),
                2 => Mage::helper('udropship')->__('2nd level subdomain (vendor.com)'),
                3 => Mage::helper('udropship')->__('3rd level subdomain (vendor.domain.com)'),
                4 => Mage::helper('udropship')->__('4th level subdomain (vendor.subdomain.domain.com)'),
                5 => Mage::helper('udropship')->__('5th level subdomain (vendor.subdomain2.subdomain1.domain.com)'),
            );
            if ($this->getPath()=='subdomain_level') {
                $options[0] = Mage::helper('udropship')->__('* Use Config');
            }
            break;

        case 'udropship/microsite/auto_approve':
            $options = array(
                self::AUTO_APPROVE_NO => Mage::helper('udropship')->__('No'),
                self::AUTO_APPROVE_YES => Mage::helper('udropship')->__('Yes'),
                self::AUTO_APPROVE_YES_ACTIVE => Mage::helper('udropship')->__('Yes and activate'),
            );
            break;

        case 'udropship/stock/stick_microsite':
            $options = array(
                0 => Mage::helper('udropship')->__('No'),
                1 => Mage::helper('udropship')->__('Yes'),
                2 => Mage::helper('udropship')->__('Yes and display vendor'),
                3 => Mage::helper('udropship')->__('Yes (only when in stock)'),
                4 => Mage::helper('udropship')->__('Yes (only when in stock) and display vendor'),
            );
            break;

        case 'is_limit_categories':
            $options = array(
                0 => Mage::helper('udropship')->__('No'),
                1 => Mage::helper('udropship')->__('Enable Selected'),
                2 => Mage::helper('udropship')->__('Disable Selected'),
            );
            break;

        case 'udropship/microsite/registration_carriers':
            $options = Mage::getSingleton('udropship/source')->getCarriers();
            $selector = false;
            break;

        case 'udropship/microsite/template_vendor':
            $options = Mage::getSingleton('udropship/source')->getVendors(true);
            $selector = false;
            break;

        case 'udropship/microsite/registration_services': // not used
            $options = array();
            $collection = $hlp->getShippingMethods();
            foreach ($collection as $shipping) {
                $options[$shipping->getId()] = $shipping->getShippingTitle().' ['.$shipping->getShippingCode().']';
            }
            $selector = false;
            break;

        case 'limit_websites':
        case 'udropship/microsite/staging_website':
            $collection = Mage::getModel('core/website')->getResourceCollection();
            $options = array('' => Mage::helper('udropship')->__('* None'));
            foreach ($collection as $w) {
                $options[$w->getId()] = $w->getName();
            }
            break;

        case 'carrier_code':
        case 'registration_carriers':
            $options = array();
            $carriers = explode(',', Mage::getStoreConfig('udropship/microsite/registration_carriers'));
            foreach ($carriers as $code) {
                $options[$code] = Mage::getStoreConfig("carriers/{$code}/title");
            }
            break;
            
        case 'udropship/microsite/hide_product_attributes':
            $options = $this->getVisibleProductAttributes();
            break;

        case 'cms_landing_page':
            $_options = Mage::getSingleton('adminhtml/system_config_source_cms_page')->toOptionArray();
            $options[-1] = Mage::helper('udropship')->__('* Use config');
            foreach ($_options as $_opt) {
                $options[$_opt['value']] = $_opt['label'];
            }
            break;

        default:
            Mage::throwException(Mage::helper('udropship')->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>Mage::helper('udropship')->__('* Please select')) + $options;
        }

        return $options;
    }
    
    protected $_visibleProductAttributes;
    public function getVisibleProductAttributes()
    {
        if (!$this->_visibleProductAttributes) {
            $entityType = Mage::getSingleton('eav/config')->getEntityType('catalog_product');
            $attrs = $entityType->getAttributeCollection()
                ->addFieldToFilter('is_visible', 1)
                ->addFieldToFilter('attribute_code', array('nin'=>array('', 'udropship_vendor')))
                ->setOrder('frontend_label', 'asc');
            $this->_visibleProductAttributes = array();
            foreach ($attrs as $a) {
                $this->_visibleProductAttributes[$a->getAttributeCode()] = $a->getFrontendLabel().' ['.$a->getAttributeCode().']';
            }
        }
        return $this->_visibleProductAttributes;
    }
}
