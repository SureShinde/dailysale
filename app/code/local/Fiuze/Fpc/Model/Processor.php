<?php
/**
 * Full Page Cache
 *
 * @author      ahofs
 * @category    Fiuze
 * @package     Fpc
 * @copyright   Copyright (c) 2015 Fiuze
 */
class Fiuze_Fpc_Model_Processor extends Mirasvit_Fpc_Model_Processor
{
    /**
     * Check if this request are allowed for process.
     *
     * @return bool
     */
    public function canProcessRequest($request = null)
    {
        if ($this->_canProcessRequest !== null) {
            return $this->_canProcessRequest;
        }

        if (!function_exists('http_response_code')) {
            function http_response_code($code = NULL) {
                $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
                return $code;
            }
        }

        if (http_response_code() != 200) {
            $this->_canProcessRequest = false;
            return $this->_canProcessRequest;
        }

        foreach (Mage::app()->getResponse()->getHeaders() as $header) {
            if ($header['name'] == 'Location') {
                $this->_canProcessRequest = false;
                return $this->_canProcessRequest;
            }
        }

        if ($request && $request->getActionName() == 'noRoute') {
            $this->_canProcessRequest = false;
            return $this->_canProcessRequest;
        }

        if ($request) {
            if (Mage::helper('mstcore')->isModuleInstalled('Fishpig_NoBots')) {
                if (($bot = Mage::helper('nobots')->getBot(false)) !== false) {
                    if ($bot->isBanned()) {
                        $this->_canProcessRequest = false;
                        return $this->_canProcessRequest;
                    }
                }
            }
        }

        $result = Mage::app()->useCache('fpc');

        if ($result) {
            $result = !isset($_GET['no_cache']);
        }

        if ($result) {
            $result = !(count($_POST) > 0);
        }


        if ($result) {
            $result = Mage::app()->getStore()->getId() != 0;
        }

        if ($result) {
           $result = $this->getConfig()->getCacheEnabled(Mage::app()->getStore()->getId());
        }

        if ($result && isset($_GET) && isset($_GET['no_cache'])) {
            $result = false;
        }

        if ($result) {
            $regExps = $this->getConfig()->getAllowedPages();
            if (count($regExps) > 0) {
                $result = false;
            }
            foreach ($regExps as $exp) {
                if (preg_match($exp, Mage::helper('fpc')->getNormlizedUrl())) {
                    $result = true;
                }
            }
        }

        if ($result) {
            $regExps = $this->getConfig()->getIgnoredPages();
            foreach ($regExps as $exp) {
                if (preg_match($exp, Mage::helper('fpc')->getNormlizedUrl())) {
                    $result = false;
                }
            }
        }

        if ($request) {
            $action = $request->getModuleName().'/'.$request->getControllerName().'_'.$request->getActionName();
            if ($result && count($this->getConfig()->getCacheableActions())) {
                $result = in_array($action, $this->getConfig()->getCacheableActions());
            }
        }

        if ($result && isset($_GET)) {
            $maxDepth = $this->getConfig()->getMaxDepth();
            /** @var $helper Fiuze_Fpc_Helper_Data */
            $helper = Mage::helper('fpc');
            $ignoreParams = $helper->getIgnoreParams();
            $ignoreParams = array_flip($ignoreParams);
            $params = array_diff_key($_GET, $ignoreParams);
            $result = count($params) <= $maxDepth;
        }

        $messageTotal = Mage::getSingleton('core/session')->getMessages()->count()
                + Mage::getSingleton('checkout/session')->getMessages()->count()
                + Mage::getSingleton('customer/session')->getMessages()->count()
                + Mage::getSingleton('catalog/session')->getMessages()->count();

        if ($result && $messageTotal) {
            $result = false;
        }

        $this->_canProcessRequest = $result;

        return $this->_canProcessRequest;
    }
}
