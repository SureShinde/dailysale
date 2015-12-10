<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

class MageWorx_CustomerCredit_Model_Rules_Condition_Action extends MageWorx_CustomerCredit_Model_Rules_Condition_Abstract
{
    public function loadAttributeOptions() {
        $attributes = array(
            'review_product'    => Mage::helper('salesrule')->__('Review product'),
            'tag_product'       => Mage::helper('salesrule')->__('Tag product'),
            'fb_like'           => Mage::helper('salesrule')->__('Facebook Like'),
            'newsletter_signup' => Mage::helper('salesrule')->__('Newsletter signup'),
//            'place_order'       => Mage::helper('salesrule')->__('Place Order Profit'), # DEPRICATED 2.6.0 -> Use 50% in other actions
            'first_order'         => Mage::helper('salesrule')->__('Place First Order'),
            
        );
        $this->setAttributeOption($attributes);
        return $this;
    }
    
    public function loadOperatorOptions() {
        $this->setOperatorOption(array(
            '=='  => Mage::helper('rule')->__('is'),
            '>='  => Mage::helper('rule')->__('equals or greater than'),
        ));
        $this->setOperatorByInputType(array(
            'string' => array('==', '>='),
        ));
        return $this;
    }
    
   
}
