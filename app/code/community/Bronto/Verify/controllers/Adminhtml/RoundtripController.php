<?php

/**
 * API Roundtrip Validate Controller
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Adminhtml_RoundtripController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Briefly validates roundtrip via ajax.
     */
    public function AjaxvalidationAction()
    {
        $response = 'Failed Verification';
        try {
            // Process Roundtrip
            $result = Mage::getModel('bronto_verify/roundtrip')->processRoundtrip();

            if ($result) {
                $response = 'Passed Verification';
            }
        } catch (Exception $e) {
            Mage::helper('bronto_verify/roundtrip')->writeError($e);
        }

        Mage::helper('bronto_verify/roundtrip')->writeDebug($result);
        Mage::helper('bronto_verify/roundtrip')->writeDebug($response);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
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
