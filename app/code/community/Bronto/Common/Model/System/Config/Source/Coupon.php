<?php

class Bronto_Common_Model_System_Config_Source_Coupon
{
    private $_options;

    /**
     * Gathers all of the sales rules on the system
     *
     * @return array
     */
    protected function _fillOptions($default = false)
    {
        $options = array();
        /** @var Mage_SalesRule_Model_Resource_Rule_Collection $rules */
        $now = Mage::getModel('core/date')->date('Y-m-d');
        $rules = Mage::getModel('salesrule/rule')->getCollection()
            ->addFieldToFilter('is_active', array('eq' => 1))
            ->addFieldToFilter('coupon_type', array('in' => array(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC, Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO)))
            ->addFieldToFilter('from_date', array(
                array('null' => true),
                array('lteq' => $now)
            ))
            ->addFieldToFilter('to_date', array(
                array('null' => true),
                array('gteq' => $now)
            ))
            ->setOrder('sort_order');
        if (!Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(4, 5, 6, 10, 11, array('major' => 9, 'edition' => 'Professional')))) {
            $rules->addFieldToFilter('use_auto_generation', array('eq' => 0));
        }

        // If there are any rules
        if ($rules->getSize()) {
            // Cycle Through Rules
            foreach ($rules as $rule) {
                // Handle Coupon Label
                $couponLabel = '(Coupon: *Auto Generated*)';
                if ($couponCode = $rule->getPrimaryCoupon()->getCode()) {
                    $couponLabel = "(Coupon: {$couponCode})";
                }

                // Build Option
                $options[] = array(
                    'label' => "{$rule->getName()} {$couponLabel}",
                    'value' => $rule->getRuleId(),
                );
            }
        }

        $noneSelected = '-- None Selected --';
        if ($default) {
            $noneSelected = '-- Use Default --';
        }

        // Add -- None Selected -- Option
        array_unshift($options, array(
            'label' => Mage::helper('bronto_common')->__($noneSelected),
            'value' => ''
        ));

        return $options;
    }

    /**
     * Retrieve option array of sales rules
     *
     * @return array
     */
    public function toOptionArray($noneSelected = false)
    {
        if (empty($this->_options)) {
            $this->_options = $this->_fillOptions($noneSelected);
        }
        return $this->_options;
    }
}
