<?php

/**
 * Conflict Checker Controller
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Adminhtml_ConflictcheckerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Briefly validates roundtrip via ajax.
     */
    public function AjaxvalidationAction()
    {
        $globalDataStore = Mage::getModel('bronto_verify/config_datastore');
        Mage::register('conflict_datastore', $globalDataStore);
        $config = Mage::getModel('bronto_verify/core_config');
        $config->reinit();

        //  Chain of Responsibility
        //  each checker looks through its designated area for rewrites
        $blocks    = Mage::getModel('bronto_verify/config_blocks');
        $models    = Mage::getModel('bronto_verify/config_models', array($blocks));
        $helpers   = Mage::getModel('bronto_verify/config_helpers', array($models));
        $resources = Mage::getModel('bronto_verify/config_resources', array($helpers));
        $checker   = Mage::getModel('bronto_verify/config_checker', array($resources));

        $checker->getConflicts($config->getNode('frontend'));

        $globalDataStore->getRewriteConflicts();

        $printer = new Bronto_Verify_Model_Config_Printer();

        $this->getResponse()->setBody($printer->render($globalDataStore, 'XML configurations rewritten more than once'));
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
