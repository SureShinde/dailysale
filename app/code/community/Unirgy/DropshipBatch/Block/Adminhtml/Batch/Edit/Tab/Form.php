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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setDestElementId('batch_form');
    }

    protected function _prepareForm()
    {
        $batch = Mage::registry('batch_data');
        $hlp = Mage::helper('udbatch');
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('batch_form', array(
            'legend'=>$hlp->__('Batch Info')
        ));

        $fieldset->addField('vendor_id', 'note', array(
            'name'      => 'vendor_id',
            'label'     => $hlp->__('Vendor'),
            'text'      => Mage::getSingleton('udropship/source')->setPath('vendors')->getOptionLabel($batch->getVendorId()),
        ));

        $fieldset->addField('batch_status', 'select', array(
            'name'      => 'batch_status',
            'label'     => $hlp->__('Status'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udbatch/source')->setPath('batch_status')->toOptionHash(),
        ));

        $fieldset->addField('num_rows', 'text', array(
            'name'      => 'num_rows',
            'label'     => $hlp->__('Number of Rows'),
            'disabled'  => true,
        ));

        $fieldset->addField('notes', 'textarea', array(
            'name'      => 'notes',
            'label'     => $hlp->__('Notes'),
        ));

        $fieldset->addField('rows_text', 'textarea', array(
            'name'      => 'rows_text',
            'readonly'  => true,
            'class'     => 'nowrap',
            'label'     => $hlp->__('Content'),
        ));

        if ($batch) {
            $form->setValues($batch->getData());
        }

        return parent::_prepareForm();
    }

}