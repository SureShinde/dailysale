<?php

/**
 * Observer Model
 *
 * @category    Fiuze
 * @package     Fiuze_All
 * @author      Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_All_Model_Observer{

    /**
     * Change product availability
     *
     * 'Automatically Return Credit Memo Item to Stock' should be set to 'Yes'
     * you can change this option in the Catalog->Inventory->Product Stock Options tab
     *
     * use event 'sales_order_creditmemo_save_after'
     *
     * @param Varien_Event_Observer $observer
     */
    public function returnProductToStock(Varien_Event_Observer $observer){
        $creditmemo = $observer->getEvent()->getCreditmemo();
        foreach($creditmemo->getAllItems() as $item){

            /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */
            if($item->hasBackToStock()){
                if($item->getQty()){
                    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId());
                    $stock->setData('is_in_stock', 1);
                    $stock->save();
                }
            }
        }
    }
} 