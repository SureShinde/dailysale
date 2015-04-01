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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPayout_Block_Adminhtml_Payout_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'udpayout';
        $this->_controller = 'adminhtml_payout';

        if ($this->getRequest()->getParam($this->_objectId)) {
            $this->_updateButton('delete', 'label', Mage::helper('udropship')->__('Delete Payout'));
            $model = Mage::getModel('udpayout/payout')
                ->load($this->getRequest()->getParam($this->_objectId));
            Mage::register('payout_data', $model);
            if ($model->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                && $model->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN
                && $model->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED
                && $model->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_HOLD
            ) {
                $this->_addButton('save_pay', array(
                    'label'     => Mage::helper('adminhtml')->__('Save and Pay'),
                    'onclick'   => "\$('pay_flag').value=1; editForm.submit();",
                    'class'     => 'save',
                ), 1);
            }
        } else {
            $this->_updateButton('save', 'label', Mage::helper('udropship')->__('Create Payout(s)'));
        }
    }

    public function getHeaderText()
    {
        return Mage::helper('udpayout')->__('Payout');
    }
}
