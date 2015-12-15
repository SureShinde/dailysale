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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Block_Adminhtml_Po_Comments_View extends Mage_Adminhtml_Block_Sales_Order_Comments_View
{
    public function getStatuses()
    {
        $_statuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        if (!Mage::getStoreConfigFlag('udropship/vendor/allow_forced_po_status_change')) {
            $_allowedPoStatuses = Mage::helper('udpo')->getAllowedPoStatuses($this->getEntity(), false);
            $__statuses = array();
            foreach ($_statuses as $_status => $_statusLbl) {
                if (in_array($_status, $_allowedPoStatuses)) {
                    $__statuses[$_status] = $_statusLbl;
                }
            }
        } else {
            $__statuses = $_statuses;
        }
        return $__statuses;
    }
    public function canSendCommentEmail()
    {
        return false;
    }
}