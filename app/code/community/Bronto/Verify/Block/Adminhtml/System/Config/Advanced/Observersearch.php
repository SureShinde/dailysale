<?php

/**
 * Permission Table Generator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Advanced_Observersearch
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Parity bit
     *
     * @var integer
     * @access protected
     */
    protected $_i = 0;

    /**
     * psuedo constructor
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/verify/advanced/observer.phtml');
    }

    /**
     * Get if even or odd
     *
     * @return string
     * @access public
     */
    public function getParity()
    {
        return $this->_i++ % 2 ? 'even' : '';
    }
}