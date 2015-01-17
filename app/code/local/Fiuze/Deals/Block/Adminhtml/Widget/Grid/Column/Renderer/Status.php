<?php
class Fiuze_Deals_Block_Adminhtml_Widget_Grid_Column_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        switch ($row->getActive()){
            case 'Disabled':
                return '<div class="disabled_deal">DISABLED</div>';
                break;
            case 'Ended':
                return '<div class="ended">ENDED</div>';
                break;
        }
    }

}