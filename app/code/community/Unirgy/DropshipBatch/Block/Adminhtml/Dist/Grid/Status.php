<?php

class Unirgy_DropshipBatch_Block_Adminhtml_Dist_Grid_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $hlp = Mage::helper('udbatch');

        $key = $this->getColumn()->getIndex();
        $value = $row->getData($key);

        switch ($key) {
        case 'dist_status':
            $classes = array(
                'pending'    => 'notice',
                'processing' => 'major',
                'exporting'  => 'major',
                'importing'  => 'major',
                'success'    => 'notice',
                'empty'      => 'minor',
                'error'      => 'critical',
                'canceled'   => 'minor'
            );
            $labels = Mage::getSingleton('udbatch/source')->setPath('dist_status')->toOptionHash();
            break;

        case 'batch_status':
            $classes = array(
                'pending'    => 'notice',
                'scheduled'  => 'notice',
                'missed'     => 'minor',
                'processing' => 'major',
                'exporting'  => 'major',
                'importing'  => 'major',
                'empty'      => 'minor',
                'success'    => 'notice',
                'partial'    => 'minor',
                'error'      => 'critical',
                'canceled'   => 'minor',
            );
            $labels = Mage::getSingleton('udbatch/source')->setPath('batch_status')->toOptionHash();
            break;

        default:
            return $value;
        }

        return '<span class="grid-severity-'.$classes[$value].'" '.(!empty($styles[$value])?' style="'.$styles[$value].'"':'').'><span style="white-space:nowrap">'
            .$labels[$value]
            .'</span></span>';
    }
}