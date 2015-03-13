<?php

class Unirgy_DropshipMicrositePro_Model_TemplateFilter extends Mage_Widget_Model_Template_Filter
{
    public function __construct()
    {
        parent::__construct();
        $_vendor = Mage::helper('umicrosite')->getCurrentVendor();
        if ($_vendor) {
            $this->_templateVars['currentVendor'] = Mage::helper('umicrosite')->getCurrentVendor();
            $this->_templateVars['vacationStatus'] = Mage::helper('umicrosite')->getCurrentVendor()->getVacationStatus()*1;
            if (Mage::helper('udropship')->isModuleActive('Unirgy_DropshipVendorRatings')) {
                $this->_templateVars['currentVendorReviewsSummaryHtml'] = Mage::helper('udratings')->getReviewsSummaryHtml($_vendor);
            }
            $this->_templateVars['currentVendorLandingPageTitle'] = Mage::helper('umicrosite')->getLandingPageTitle($_vendor);
        }
    }
}