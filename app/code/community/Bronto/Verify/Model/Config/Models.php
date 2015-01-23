<?php

/**
 * Model Config Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Config_Models
    extends Bronto_Verify_Model_Config_ConfigAbstract
{
    /**
     * Type of rewrite
     *
     * @var string
     * @access protected
     */
    protected $_type = 'models';

    /**
     * Check models section for rewrites
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
        $models = $config->models;
        $this->_findRewrites($models, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
