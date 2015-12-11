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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_Block_Adminhtml_Cms_Page_Grid extends Mage_Adminhtml_Block_Cms_Page_Grid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cms/page')->getCollection();

        $vendor = Mage::helper('umicrosite')->getCurrentVendor();
        if ($vendor) {
            $collection->addFieldToFilter('udropship_vendor', $vendor->getId());
        }

        /* @var $collection Mage_Cms_Model_Mysql4_Page_Collection */
        $collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        if (!Mage::helper('umicrosite')->getCurrentVendor()) {
            $this->addColumn('udropship_vendor', array(
                'header'  => Mage::helper('udropship')->__('Dropship Vendor'),
                'index'   => 'udropship_vendor',
                'type'    => 'options',
                'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
                'filter' => 'udropship/vendor_gridColumnFilter'
            ));
        }
    }
}
