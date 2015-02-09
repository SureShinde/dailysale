<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Widget_Grid_Column_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{

    public function render(Varien_Object $row){
        switch($row->getDealsActive()){
            case 0:
                return '<div style="background-color: #CCC;">DISABLED</div>';
                break;
            case 1:
                return '<div style="background-color: #0BFCAD;">ENABLED</div>';
                break;
        }
    }

}