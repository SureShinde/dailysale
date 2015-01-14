<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Salesrule extends Bronto_Common_Helper_Data
{
    /**
     * Load Sales Rule by ID
     *
     * @param $ruleId
     *
     * @return bool|Mage_Core_Model_Abstract
     */
    public function getRuleById($ruleId)
    {
        try {
            $rule = Mage::getModel('salesrule/rule')->load($ruleId);
        } catch (Exception $e) {
            $this->writeError('Failed loading Rule for ID: ' . $ruleId);

            return false;
        }

        return $rule;
    }

    /**
     * Retrieve Option array of Sales Rules
     *
     * @return array
     */
    public function getRuleOptionsArray()
    {
        return Mage::getModel('bronto_common/system_config_source_coupon')
            ->toOptionArray();
    }
}
