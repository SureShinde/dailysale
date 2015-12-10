<?php

/**
 * Validate File Owner
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_Owner
    extends Bronto_Verify_Model_Validator_ValidatorAbstract
{
    protected $_targetOwner = null;

    protected function _construct()
    {
        $this->_targetOwner = Mage::getStoreConfig('bronto_verify/permissionchecker/owner');
        parent::_construct();
    }

    /**
     * Validate Owner
     *
     * Checks to see if file owner setting matches expected
     *
     * @param SplFileInfo $file     File to check
     * @param array       $badFiles current array of bad files to report
     *
     * @return array
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles)
    {
        if (!empty($this->_targetOwner)) {
            //  Account for name and/or gid
            if (filter_var($this->_targetOwner, FILTER_VALIDATE_INT)) {
                $actualOwner = $file->getOwner();
            } else {
                $owner       = posix_getpwuid($file->getOwner());
                $actualOwner = $owner['name'];
            }
            if ($actualOwner != $this->_targetOwner) {
                $path                     = substr_replace($file->__toString(), '', 0, strlen(Mage::getBaseDir()) + 1);
                $badFiles[$path]['owner'] = $actualOwner;
            }
        }

        return parent::validateSetting($file, $badFiles);
    }
}
