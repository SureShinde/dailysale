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

class Unirgy_DropshipPayout_Block_Adminhtml_Payout_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setDestElementId('payout_form');
    }

    protected function _prepareForm()
    {
        $payout = Mage::registry('payout_data');
        $hlp = Mage::helper('udpayout');
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $v = Mage::helper('udropship')->getVendor($payout->getVendorId());

        $fieldset = $form->addFieldset('payout_form', array(
            'legend'=>$hlp->__('Payout Info')
        ));

        $fieldset->addField('pay_flag', 'hidden', array(
            'name'      => 'pay_flag',
        ));
        
        $fieldset->addField('vendor_id', 'note', array(
            'name'      => 'vendor_id',
            'label'     => $hlp->__('Vendor'),
            'text'      => sprintf('<a href="%s">%s</a>', $this->getUrl('udropshipadmin/adminhtml_vendor/edit', array('id'=>$payout->getVendorId())), Mage::getSingleton('udropship/source')->setPath('vendors')->getOptionLabel($payout->getVendorId())),
        ));
        
        $fieldset->addField('statement_id', 'note', array(
            'name'      => 'statement_id',
            'label'     => $hlp->__('Statement ID'),
            'text'      => $payout->getStatement()->getId() 
                ? sprintf('<a href="%s">%s</a>', $this->getUrl('udropshipadmin/adminhtml_vendor_statement/edit', array('id'=>$payout->getStatement()->getId())), $payout->getStatementId()) 
                : ''
        ));

        $fieldset->addField('payout_type', 'select', array(
            'name'      => 'payout_type',
            'label'     => $hlp->__('Type'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udpayout/source')->setPath('payout_type_internal')->toOptionHash(),
        ));
        
        $fieldset->addField('payout_method', 'select', array(
            'name'      => 'payout_method',
            'label'     => $hlp->__('Method'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udpayout/source')->setPath('payout_method')->toOptionHash(),
        ));

        try {
            $method = $payout->getMethodInstance();
            if ($method && $method->hasExtraInfo($payout)) {
                $fieldset->addField('payout_method_details', 'note', array(
                    'name'      => 'payout_method_details',
                    'label'     => $hlp->__('Method Specific Details'),
                    'text'      => $method->getExtraInfoHtml($payout)
                ));
            }
        } catch (Exception $e) {}

        if ($v->getData('payout_details')) {
            $fieldset->addField('vendor_payout_details', 'note', array(
                'name'      => 'vendor_payout_details',
                'label'     => $hlp->__('Payout Additional Details'),
                'text'      => $this->escapeHtml($v->getData('payout_details'))
            ));
        }
        
        $fieldset->addField('transaction_id', 'note', array(
            'name'      => 'transaction_id',
            'label'     => $hlp->__('Transaction ID'),
            'text'      => $payout->getData('transaction_id')
        ));
        
        if ($payout->getData('payout_method') == 'paypal') {
            $fieldset->addField('paypal_correlation_id', 'note', array(
                'name'      => 'transaction_id',
                'label'     => $hlp->__('Paypal Correlation ID'),
                'text'      => $payout->getData('paypal_correlation_id')
            ));
        }
        
        $fieldset->addField('payout_status', 'select', array(
            'name'      => 'payout_status',
            'label'     => $hlp->__('Status'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udpayout/source')->setPath('payout_status')->toOptionHash(),
        ));
        
        $fieldset->addField('po_type', 'select', array(
            'name'      => 'po_type',
            'label'     => $hlp->__('Po Type'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udropship/source')->setPath('statement_po_type')->toOptionHash(),
        ));

        $fieldset->addField('total_orders', 'note', array(
            'name'      => 'total_orders',
            'label'     => $hlp->__('Number of Orders'),
            'text'      => $payout->getData('total_orders')
        ));
        
        $fieldset->addField('transaction_fee', 'note', array(
            'name'      => 'transaction_fee',
            'label'     => $hlp->__('Transaction Fee'),
            'text' => Mage::helper('core')->formatPrice($payout->getData('transaction_fee'))
        ));

        if (!Mage::helper('udropship')->isStatementAsInvoice()) {
            $fieldset->addField('total_payout', 'note', array(
                'name'      => 'total_payout',
                'label'     => $hlp->__('Total Payout'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('total_payout'))
            ));

            $fieldset->addField('total_paid', 'note', array(
                'name'      => 'total_paid',
                'label'     => $hlp->__('Total Paid'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('total_paid'))
            ));

            $fieldset->addField('total_due', 'note', array(
                'name'      => 'total_due',
                'label'     => $hlp->__('Total Due'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('total_due'))
            ));
        } else {
            $fieldset->addField('total_payment', 'note', array(
                'name'      => 'total_payment',
                'label'     => $hlp->__('Total Payment'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('total_payment'))
            ));

            $fieldset->addField('payment_paid', 'note', array(
                'name'      => 'payment_paid',
                'label'     => $hlp->__('Payment Paid'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('payment_paid'))
            ));

            $fieldset->addField('payment_due', 'note', array(
                'name'      => 'payment_due',
                'label'     => $hlp->__('Payment Due'),
                'text' => Mage::helper('core')->formatPrice($payout->getData('payment_due'))
            ));
        }

        $fieldset->addField('notes', 'textarea', array(
            'name'      => 'notes',
            'label'     => $hlp->__('Notes'),
        ));
        
        if (!($payout->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                || $payout->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED
                || $payout->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_HOLD)
        ) {
            $fieldset->addField('adjustment', 'text', array(
                'name'      => 'adjustment',
                'label'     => $hlp->__('Adjustment'),
                'value_filter' => new Varien_Filter_Sprintf('%s', 2),
            ))
            ->setRenderer(
                $this->getLayout()->createBlock('udropship/adminhtml_vendor_helper_renderer_adjustment')->setStatement($payout)
            );
        }
        
        $fieldset->addField('error_info', 'note', array(
            'name'      => 'error_info',
            'label'     => $hlp->__('Messages'),
            'text'      => nl2br($payout->getErrorInfo())
        ));

        if ($payout) {
            $form->setValues($payout->getData());
        }

        return parent::_prepareForm();
    }

}
