<?php

/**
 * File Filter Iterator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_Filter_PatternIterator
    extends RecursiveFilterIterator
{
    /**
     * Check file name to see if it matches anything that needs to be filtered
     *
     * @return boolean
     * @access public
     */
    public function accept()
    {
        return Mage::helper('bronto_verify/permissionchecker')->accept($this->current()->getBasename());
    }
}
