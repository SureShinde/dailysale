<?php

class Bronto_Common_Adminhtml_DebugController extends Mage_Adminhtml_Controller_Action
{

    protected $_helper;

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('bronto_common/support');
        }

        return $this->_helper;
    }

    /**
     * Sets this helper
     *
     * @param Mage_Core_Helper_Data $helper
     *
     * @return Bronto_Common_Adminhtml_DebugController
     */
    public function setHelper(Mage_Core_Helper_Data $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * Retrieves the system information in JSON via ajax request
     */
    public function collectAction()
    {
        $debug = $this->_getHelper()->getDebugInformation();

        // Magento 1.4, 1.5, and 1.9 chokes on the json encoding array values
        // PHP json_encode exists on the server, then use it
        if (function_exists('json_encode')) {
            $json = json_encode($debug);
        } else {
            $json = Mage::helper('core')->jsonEncode($debug);
        }

        $this
            ->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json);
    }

    /**
     * Runs the API send queue
     */
    public function sendAction()
    {
        $helper = Mage::helper('bronto_common/api');
        $result = Mage::getModel('bronto_common/observer')->processSendForScope();
        $this->_getSession()->addSuccess(sprintf("Processed %d Deliveries (%d Error / %d Success)", $result['total'], $result['error'], $result['success']));
        $returnParams = array('section' => 'bronto_api');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Sends an archive to the browser
     */
    public function archiveAction()
    {
        $zip  = basename($this->_getHelper()->getLogArchive()->getFilename());
        $json = Mage::helper('core')->jsonEncode(array(
            'name' => $zip,
            'link' => $this->getUrl('*/*/download', array('file' => $zip)),
        ));

        $this
            ->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json);
    }

    /**
     * Sends the zip to the browser
     */
    public function downloadAction()
    {
        $file           = $this->getRequest()->getParam('file');
        $baseArchiveDir = $this->_getHelper()->getArchiveDirectory();

        if (!file_exists($baseArchiveDir . DS . $file)) {
            Mage::getSingleton('adminhtml/session')->addError("Archive '$file' does not exist.");

            return $this->_redirect('*/system_config/edit', array('section' => 'bronto'));
        } else {
            $this
                ->getResponse()
                ->setHeader('Content-Description', 'File Transfer')
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', 'attachment; filename="bronto_logs_' . time() . '.zip"')
                ->clearBody();

            $this->getResponse()->sendHeaders();
            ob_end_flush();
            readfile($baseArchiveDir . DS . $file);
            exit;
        }
    }
}
