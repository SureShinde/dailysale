<?php

/**
 * Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Conflictchecker
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * rewritten xml nodes
     *
     * @var array
     * @access protected
     */
    protected $_rewrittenConfigs = array();

    /**
     * Render all xml names that conflict
     *
     * @param Varien_Data_Form_Element_Abstract $element Form element
     *
     * @return string
     * @access public
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $globalDataStore = Mage::getModel('bronto_verify/config_datastore');
        Mage::register('conflict_datastore', $globalDataStore);
        $config = Mage::getModel('bronto_verify/core_config');
        $config->reinit();

        //  Chain of Responsibility
        //  each checker looks through its designated area for rewrites
        $blocks    = Mage::getModel('bronto_verify/config_blocks');
        $models    = Mage::getModel('bronto_verify/config_models', array($blocks));
        $helpers   = Mage::getModel('bronto_verify/config_helpers', array($models));
        $resources = Mage::getModel('bronto_verify/config_resources', array($helpers));
        $checker   = Mage::getModel('bronto_verify/config_checker', array($resources));

        $checker->getConflicts($config->getNode('frontend'));

        $globalDataStore->getRewriteConflicts();

        $printer = new Bronto_Verify_Model_Config_Printer();

        return $printer->render($globalDataStore, 'XML configurations rewritten more than once');
    }
}