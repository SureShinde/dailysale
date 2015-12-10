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
 
class Unirgy_DropshipPo_Block_Adminhtml_Po_View_Comments extends Mage_Adminhtml_Block_Text_List
{
    public function getPo()
    {
        return Mage::registry('current_udpo');
    }

    public function getOrder()
    {
        return $this->getPo()->getOrder();
    }

    public function getSource()
    {
        return $this->getPo();
    }
}