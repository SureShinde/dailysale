<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_Deals extends Mage_Core_Model_Abstract{

    protected function _construct(){
        $this->_init('fiuze_deals/deals');
    }
}