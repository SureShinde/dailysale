<?php

/**
 * Validate File Group
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Validator_Group
    extends Bronto_Verify_Model_Validator_ValidatorAbstract
{
    protected $_targetGroup = null;

    protected function _construct()
    {
        $this->_targetGroup = Mage::getStoreConfig('bronto_verify/permissionchecker/group');
        parent::_construct();
    }

    /**
     * Validate Group
     *
     * Checks to see if file group setting matches expected
     *
     * @param SplFileInfo $file     File to check
     * @param array       $badFiles current array of bad files to report
     *
     * @return array
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles)
    {
        if (!empty($this->_targetGroup)) {
            //  Account for name and/or gid
            if (filter_var($this->_targetGroup, FILTER_VALIDATE_INT)) {
                $actualGroup = $file->getGroup();
            } else {
                $group       = posix_getgrgid($file->getGroup());
                $actualGroup = $group['name'];
            }
            if ($actualGroup != $this->_targetGroup) {
                $path                     = substr_replace($file->__toString(), '', 0, strlen(Mage::getBaseDir()) + 1);
                $badFiles[$path]['group'] = $actualGroup;
            }
        }

        return parent::validateSetting($file, $badFiles);
    }
}
