<?php
/**
 * DropshipPo
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipPo
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipPo_Model_Mysql4_Po_Collection extends Unirgy_DropshipPo_Model_Mysql4_Po_Collection
{
    const PO_LIMIT = 1000;

    /**
     * @return Fiuze_DropshipPo_Model_Mysql4_Po_Collection
     */
    public function addOrders()
    {
        if (!$this->isLoaded()) {
            $this->getSelect()->limit(self::PO_LIMIT);
        }

        return parent::addOrders();
    }

    public function addStockPos()
    {
        if (!$this->isLoaded()) {
            $this->getSelect()->limit(self::PO_LIMIT);
        }

        return parent::addStockPos();
    }
}
