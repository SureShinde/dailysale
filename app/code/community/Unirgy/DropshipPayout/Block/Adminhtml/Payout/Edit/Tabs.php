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

class Unirgy_DropshipPayout_Block_Adminhtml_Payout_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('payout_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('udpayout')->__('Manage Payouts'));
    }

    protected function _beforeToHtml()
    {
        $id = Mage::app()->getRequest()->getParam('id', 0);

        if ($id) {
            $payout = Mage::registry('payout_data');
            $this->addTab('form_section', array(
                'label'     => Mage::helper('udropship')->__('Payout Information'),
                'title'     => Mage::helper('udropship')->__('Payout Information'),
                'content'   => $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_form')
                    ->setVendorId($id)
                    ->toHtml(),
            ));
            $this->addTab('rows_section', array(
                'label'     => Mage::helper('udpayout')->__('Data Rows'),
                'title'     => Mage::helper('udpayout')->__('Data Rows'),
                'content'   => $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_rows', 'udpayout.rows.grid')->setVendorId($id)->toHtml(),
            ));
            $this->addTab('adjustments_section', array(
                'label'     => Mage::helper('udpayout')->__('Adjustments'),
                'title'     => Mage::helper('udpayout')->__('Adjustments'),
                'content'   => $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_adjustments', 'udpayout.adjustments.grid')->setVendorId($id)->toHtml(),
            ));
        } else {
            $this->addTab('form_section', array(
                'label'     => Mage::helper('udropship')->__('Payout Information'),
                'title'     => Mage::helper('udropship')->__('Payout Information'),
                'content'   => $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_formNew')
                    ->setVendorId($id)
                    ->toHtml(),
            ));
        }

        return parent::_beforeToHtml();
    }
}
