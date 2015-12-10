<?php

/**
 * Validate File Permissions
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_File
    extends Bronto_Verify_Model_Validator_ValidatorAbstract
{
    protected $_permission;
    protected $_permLen;

    protected function _construct()
    {
        $this->_permission = Mage::getStoreConfig('bronto_verify/permissionchecker/files');
        //  This allows us to handle how big a sub string to return
        //  which will dynamically account for extra permission bits. i.e. (sticky bits)
        $this->_permLen = strlen($this->_permission);
        parent::_construct();
    }

    /**
     * Validate file permissions
     *
     * Checks to see if file permissions match correctly
     *
     * @param SplFileInfo $file     File to check
     * @param array       $badFiles current array of bad files to report
     *
     * @return array
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles)
    {
        if ($file->isFile()) {
            $path     = substr_replace($file->getPath(), '', 0, strlen(Mage::getBaseDir()) + 1);
            $filepath = substr_replace($file->__toString(), '', 0, strlen(Mage::getBaseDir()) + 1);
            if (
                Mage::helper('bronto_verify/permissionchecker')->accept($path) &&
                Mage::helper('bronto_verify/permissionchecker')->accept($filepath)
            ) {
                $octalPerms = substr(sprintf('%o', $file->getPerms()), -$this->_permLen);
                if ($octalPerms != $this->_permission) {
                    $badFiles[$filepath]['file permission'] = $octalPerms;
                }
            }
        }

        return parent::validateSetting($file, $badFiles);
    }
}
