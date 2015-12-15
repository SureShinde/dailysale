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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPo_Model_Po_Comment extends Mage_Sales_Model_Abstract
{
    protected $_eventPrefix = 'udpo_po_comment';
    protected $_eventObject = 'po_comment';
    
    protected $_po;

    protected function _construct()
    {
        $this->_init('udpo/po_comment');
    }

    public function setPo(Unirgy_DropshipPo_Model_Po $po)
    {
        $this->_po = $po;
        return $this;
    }

    public function getPo()
    {
        return $this->_po;
    }

    public function getStore()
    {
        if ($this->getPo()) {
            return $this->getPo()->getStore();
        }
        return Mage::app()->getStore();
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        if (!$this->getParentId() && $this->getPo()) {
            $this->setParentId($this->getPo()->getId());
        }

        return $this;
    }
}
