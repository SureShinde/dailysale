<?php

/**
 * Permission Checker Controller
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Adminhtml_PermissioncheckerController extends Mage_Adminhtml_Controller_Action
{
    public function verifyAction()
    {
        $this->loadLayout()->renderLayout();
    }

    /**
     * Briefly validates roundtrip via ajax.
     */
    public function AjaxvalidationAction()
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
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $invalidFiles = $checker->validateSettings($iterator);

        $printer = new Bronto_Verify_Model_Validator_Printer();

        $this->getResponse()->setBody($printer->render($invalidFiles));
    }

    /**
     * @return bool
     * @access protected
     */
    protected function _isAllowed()
    {
        return $this->_isSectionAllowed('bronto_verify');
    }

    /**
     * Check if specified section allowed in ACL
     *
     * Will forward to deniedAction(), if not allowed.
     *
     * @param string $section
     *
     * @return bool
     * @access protected
     */
    protected function _isSectionAllowed($section)
    {
        try {
            $session        = Mage::getSingleton('admin/session');
            $resourceLookup = "admin/system/config/{$section}";
            if ($session->getData('acl') instanceof Mage_Admin_Model_Acl) {
                $resourceId = $session->getData('acl')->get($resourceLookup)->getResourceId();
                if (!$session->isAllowed($resourceId)) {
                    throw new Exception('');
                }

                return true;
            }
        } catch (Zend_Acl_Exception $e) {
            $this->norouteAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        } catch (Exception $e) {
            $this->deniedAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        }

        return false;
    }
}
