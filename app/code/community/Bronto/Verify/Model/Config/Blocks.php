<?php

/**
 * Block Config Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Model_Config_Blocks
    extends Bronto_Verify_Model_Config_ConfigAbstract
{
    /**
     * Type of rewrite
     *
     * @var string
     * @access protected
     */
    protected $_type = 'blocks';

    /**
     * Check block section for rewrites
     *
     * @param Bronto_Verify_Model_Core_Config_Element $config   Config node
     * @param array                                   $rewrites Existing rewrites
     *
     * @return array rewrites
     * @access public
     */
    public function getRewrites(
        Bronto_Verify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        $blocks = $config->blocks;
        $this->_findRewrites($blocks, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
