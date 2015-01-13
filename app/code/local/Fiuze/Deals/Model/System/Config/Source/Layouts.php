<?php
/**
 * Deals Rotation
 *
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */

class Fiuze_Deals_Model_System_Config_Source_Layouts extends Mage_Core_Model_Abstract{
    protected $_options = null;

    public function toOptionArray()
    {
        if ($this->_options === null) {
            $this->_options = array();
            foreach (Mage::getSingleton('page/config')->getPageLayouts() as $layout) {
                $this->_options[] = array(
                    'value' => $layout->getTemplate(),
                    'label' => $layout->getLabel(),
                );
            }
        }
        return $this->_options;
    }
}