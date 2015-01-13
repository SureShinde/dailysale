<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */

class Fiuze_Deals_Model_Observer{

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function catalogBlockProductListCollection(Varien_Event_Observer $observer){
        $observer->getCollection()->addAttributeToSelect('*');
    }

}