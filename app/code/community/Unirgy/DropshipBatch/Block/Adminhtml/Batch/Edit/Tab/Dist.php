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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Dist extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('udbatch_batch_dist');
        $this->setDefaultSort('dist_id');
        $this->setUseAjax(true);
    }

    public function getBatch()
    {
        $batch = Mage::registry('batch_data');
        if (!$batch) {
            $batch = Mage::getModel('udbatch/vendor')->load($this->getBatchId());
            Mage::register('batch_data', $batch);
        }
        return $batch;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('udbatch/batch_dist')->getCollection()
            ->addFieldToFilter('batch_id', $this->getBatch()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('dist_id', array(
            'header'    => Mage::helper('udbatch')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'dist_id'
        ));
        $this->addColumn('location', array(
            'header'    => Mage::helper('udbatch')->__('Location'),
            'index'     => 'location'
        ));
        $this->addColumn('dist_status', array(
            'header'    => Mage::helper('udbatch')->__('Status'),
            'index'     => 'dist_status',
            'type'      => 'options',
            'options'   => Mage::getSingleton('udbatch/source')->setPath('dist_status')->toOptionHash(),
            'renderer'  => 'udbatch/adminhtml_dist_grid_status',
        ));
        $this->addColumn('error_info', array(
            'header'    => Mage::helper('udbatch')->__('Error'),
            'index'     => 'error_info'
        ));
        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('udbatch')->__('Updated At'),
            'index'     => 'updated_at',
            'type'      => 'datetime',
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/rowGrid', array('_current'=>true));
    }
}
