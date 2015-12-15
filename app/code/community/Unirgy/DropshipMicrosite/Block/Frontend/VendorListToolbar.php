<?php

class Unirgy_DropshipMicrosite_Block_Frontend_VendorListToolbar extends Mage_Page_Block_Html_Pager
{
    protected $_orderField       = 'vendor_name';
    protected $_availableOrder   = array(
        'vendor_name' => 'Name',
    );
    protected $_pageVarName     = 'p';
    protected $_orderVarName        = 'order';
    protected $_directionVarName    = 'dir';
    protected $_modeVarName         = 'mode';
    protected $_limitVarName        = 'limit';
    protected $_availableMode       = array();
    protected $_enableViewSwitcher  = true;
    protected $_isExpanded          = true;
    protected $_viewMode            = null;
    protected $_direction        = 'asc';
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('umicrosite/vendor/list_toolbar.phtml');
        $this->_availableMode = array('grid' => Mage::helper('udropship')->__('Grid'), 'list' =>  Mage::helper('udropship')->__('List'));
    }
    public function isOrderCurrent($order)
    {
        return ($order == $this->getCurrentOrder());
    }
    public function getCurrentPage()
    {
        if ($page = (int) $this->getRequest()->getParam($this->getPageVarName())) {
            return $page;
        }
        return 1;
    }
    public function getPageVarName()
    {
        return $this->_pageVarName;
    }
    public function getOrderVarName()
    {
        return $this->_orderVarName;
    }
    public function getDirectionVarName()
    {
        return $this->_directionVarName;
    }
    public function getModeVarName()
    {
        return $this->_modeVarName;
    }
    public function getLimitVarName()
    {
        return $this->_limitVarName;
    }
    public function getCurrentDirection()
    {
        $dir = $this->_getData('_current_grid_direction');
        if ($dir) {
            return $dir;
        }
        $directions = array('asc', 'desc');
        $dir = strtolower($this->getRequest()->getParam($this->getDirectionVarName()));
        if (!$dir || !in_array($dir, $directions)) {
            $dir = $this->_direction;
        }
        $this->setData('_current_grid_direction', $dir);
        return $dir;
    }
    public function getAvailableOrders()
    {
        return $this->_availableOrder;
    }
    public function setAvailableOrders($orders)
    {
        $this->_availableOrder = $orders;
        return $this;
    }
    public function addOrderToAvailableOrders($order, $value)
    {
        $this->_availableOrder[$order] = $value;
        return $this;
    }
    public function getCurrentOrder()
    {
        $order = $this->_getData('_current_grid_order');
        if ($order) {
            return $order;
        }
        $orders = $this->getAvailableOrders();
        $defaultOrder = $this->_orderField;
        if (!isset($orders[$defaultOrder])) {
            $keys = array_keys($orders);
            $defaultOrder = $keys[0];
        }
        $order = $this->getRequest()->getParam($this->getOrderVarName());
        if (!$order || !isset($orders[$order])) {
            $order = $defaultOrder;
        }
        $this->setData('_current_grid_order', $order);
        return $order;
    }
    public function getOrderUrl($order, $direction)
    {
        if (is_null($order)) {
            $order = $this->getCurrentOrder() ? $this->getCurrentOrder() : $this->_availableOrder[0];
        }
        return $this->getPagerUrl(array(
            $this->getOrderVarName()=>$order,
            $this->getDirectionVarName()=>$direction,
            $this->getPageVarName() => null
        ));
    }
    public function getCurrentMode()
    {
        $mode = $this->_getData('_current_grid_mode');
        if ($mode) {
            return $mode;
        }
        $modes = array_keys($this->_availableMode);
        $defaultMode = current($modes);
        $mode = $this->getRequest()->getParam($this->getModeVarName());
        if (!$mode) {
            $mode = $defaultMode;
        }

        if (!$mode || !isset($this->_availableMode[$mode])) {
            $mode = $defaultMode;
        }
        $this->setData('_current_grid_mode', $mode);
        return $mode;
    }
    public function isModeActive($mode)
    {
        return $this->getCurrentMode() == $mode;
    }
    public function getModes()
    {
        return $this->_availableMode;
    }
    public function setModes($modes)
    {
        if(!isset($this->_availableMode)){
            $this->_availableMode = $modes;
        }
        return $this;
    }
    public function getModeUrl($mode)
    {
        return $this->getPagerUrl( array($this->getModeVarName()=>$mode, $this->getPageVarName() => null) );
    }
    public function disableViewSwitcher()
    {
        $this->_enableViewSwitcher = false;
        return $this;
    }
    public function enableViewSwitcher()
    {
        $this->_enableViewSwitcher = true;
        return $this;
    }
    public function isEnabledViewSwitcher()
    {
        return $this->_enableViewSwitcher;
    }
    public function disableExpanded()
    {
        $this->_isExpanded = false;
        return $this;
    }
    public function enableExpanded()
    {
        $this->_isExpanded = true;
        return $this;
    }
    public function isExpanded()
    {
        return $this->_isExpanded;
    }

    public function setCollection($collection)
    {
        $this->_collection = $collection;
        $this->_collection->setCurPage($this->getCurrentPage());
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
        if ($this->getCurrentOrder()) {
            $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }
        return $this;
    }

    public function getPagerHtml()
    {
        $pagerBlock = $this->getChild('vendor_list_toolbar_pager');

        if ($pagerBlock instanceof Varien_Object) {

            /* @var $pagerBlock Mage_Page_Block_Html_Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(false)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setCollection($this->getCollection());

            return $pagerBlock->toHtml();
        }

        return '';
    }
}