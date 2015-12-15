<?php

/**
 * Permission checker
 *
 * This is the heart of the permission checker that glues together and fires
 * the Chain of responsibility
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Permissionchecker
    extends Mage_Adminhtml_Block_Abstract
{
    /**
     * Render all files that don't validate to the proper permissions
     *
     * @return string
     */
    protected function _toHtml()
    {
        //  Chain of Responsibility
        //  each checker looks through its designated area to validate the node we're at.
        $file  = Mage::getModel('bronto_verify/validator_file');
        $dir   = Mage::getModel('bronto_verify/validator_directory', array($file));
        $group = Mage::getModel('bronto_verify/validator_group', array($dir));
        $owner = Mage::getModel('bronto_verify/validator_owner', array($group));

        $checker = Mage::getModel('bronto_verify/validator_checker', array($owner));

        $directory    = new RecursiveDirectoryIterator(Mage::getBaseDir());
        $filter       = new Bronto_Verify_Model_Validator_Filter_PatternIterator($directory);
        $iterator     = new RecursiveIteratorIterator(
            $filter,
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $invalidFiles = $checker->validateSettings($iterator);

        $printer = new Bronto_Verify_Model_Validator_Printer();

        return $printer->render($invalidFiles);
    }
}