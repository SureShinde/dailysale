<?php

/**
 * Helper Config Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Config_Helpers
    extends Bronto_Verify_Model_Config_ConfigAbstract
{
    /**
     * Type of rewrite
     *
     * @var string
     * @access protected
     */
    protected $_type = 'helpers';

    /**
     * Check helper section for rewrites
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
        $helpers = $config->helpers;
        $this->_findRewrites($helpers, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
