<?php

/**
 * Directory Validator
 *
 * This is the client of the Chain of Responsibility
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_Directory
    extends Bronto_Verify_Model_Validator_ValidatorAbstract
{
    protected $_permission;
    protected $_permLen;

    protected function _construct()
    {
        $this->_permission = Mage::getStoreConfig('bronto_verify/permissionchecker/directories');
        $this->_permLen    = strlen($this->_permission);
        parent::_construct();
    }

    /**
     * Validate directory
     *
     * Checks to see if file is directory and if permissions match expected
     *
     * @param SplFileInfo $file     File to check
     * @param array       $badFiles current array of bad files to report
     *
     * @return array
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles)
    {
        if ($file->isDir()) {
            $path = substr_replace($file->__toString(), '', 0, strlen(Mage::getBaseDir()) + 1);
            if (Mage::helper('bronto_verify/permissionchecker')->accept($path)) {
                $octalPerms = substr(sprintf('%o', $file->getPerms()), -$this->_permLen);
                if ($octalPerms != $this->_permission) {
                    $badFiles[$path]['directory permission'] = $octalPerms;
                }
            }
        }

        return parent::validateSetting($file, $badFiles);
    }
}
