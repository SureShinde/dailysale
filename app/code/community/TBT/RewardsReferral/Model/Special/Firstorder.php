<?php

class TBT_RewardsReferral_Model_Special_Firstorder extends TBT_Rewards_Model_Special_Configabstract {
    const ACTION_REFERRAL_FIRST_ORDER = 'customer_referral_firstorder';

    public function _construct() {
        $this->setCaption("Customer Referral");
        $this->setDescription("Customer will get points for every purchase made by a referred customer.");
        $this->setCode("referral");
        return parent::_construct();
    }

    public function getNewCustomerConditions() {
        return array(
            self::ACTION_REFERRAL_FIRST_ORDER => Mage::helper('rewardsref')->__('Referral makes first order'),
        );
    }

    public function visitAdminConditions(&$fieldset) {
        return $this;
    }

    public function visitAdminActions(&$fieldset) {
        return $this;
    }

    public function getNewActions() {
        return array();
    }

    public function getAdminFormScripts() {
        return array();
    }

    public function getAdminFormInitScripts() {
        return array();
    }

}