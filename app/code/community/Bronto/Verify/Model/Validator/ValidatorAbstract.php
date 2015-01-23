<?php

/**
 * Abstracted Validator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
abstract class Bronto_Verify_Model_Validator_ValidatorAbstract
    extends Mage_Core_Model_Abstract
    implements Bronto_Verify_Model_Validator_ValidatorInterface
{
    /**
     * Link List
     *
     * This is the pointer to the next node in the link list
     *
     * @var object
     * @access protected
     */
    protected $_nextHandler = null;

    /**
     * pseudo constructor
     *
     * @return void
     * @access protected
     */
    protected function _construct()
    {
        if (isset($this->_data[0])) {
            $this->_nextHandler = $this->_data[0];
        }
    }

    /**
     * Validate the settings
     *
     * If there are no more links in the list, then return the growing
     * array of bad files to report on.  Else call to the next validator to
     * check the node
     *
     * @param SplFileInfo $file     File to validate
     * @param array       $badFiles existing bad files to report on
     *
     * @return array
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles)
    {
        if (!is_null($this->_nextHandler)) {
            return $this->_nextHandler->validateSetting($file, $badFiles);
        } else {
            return $badFiles;
        }
    }
}
