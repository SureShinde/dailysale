<?php

/**
 * File system checker
 *
 * This is the client of the Chain of Responsibility
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_Checker
    extends Bronto_Verify_Model_Validator_ValidatorAbstract
{
    /**
     * Validate all settings defined in the chain of responsibility
     *
     * This is the client in the chain of responsibility
     *
     * @param RecursiveIteratorIterator $path Path to the beginning of the directory tree
     *
     * @return array                     All the files which were found that deviate from the expected settings
     * @access public
     */
    public function validateSettings(RecursiveIteratorIterator $path)
    {
        $badFiles = array();
        foreach ($path as $fileInfo) {
            $badFiles = $this->validateSetting($fileInfo, $badFiles);
        }

        return $badFiles;
    }
}
