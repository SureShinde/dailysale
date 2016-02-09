<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_Model_Rewrite_Compiler_Process extends Mage_Compiler_Model_Process
{
    const CERTIFICATES_FOLDER_PATH  = '/../ssl/';
    const CERTIFICATE_NAME          = 'api_braintreegateway_com.ca.crt';
    const CERTIFICATE_SANDBOX_NAME  = 'sandbox_braintreegateway_com.ca.crt';

    /**
     * Copy directory
     *
     * Comparing to base method also copies certificate files required by Braintree extension
     * 
     * @param   string $source
     * @param   string $target
     * @return  Mage_Compiler_Model_Process
     */
    protected function _copy($source, $target, $firstIteration = true)
    {
        if (is_dir($source)) {
            $dir = dir($source);
            while (false !== ($file = $dir->read())) {
                if (($file[0] == '.')) {
                    continue;
                }
                $sourceFile = $source . DS . $file;
                if ($file == 'controllers') {
                    $this->_controllerFolders[] = $sourceFile;
                    continue;
                }

                if ($firstIteration) {
                    $targetFile = $target . DS . $file;
                } else {
                    $targetFile = $target . '_' . $file;
                }
                $this->_copy($sourceFile, $targetFile, false);
            }
        } else {
            if (strpos($source, self::CERTIFICATE_NAME) !== false) {
                if (!file_exists($this->_includeDir . self::CERTIFICATES_FOLDER_PATH)) {
                    mkdir($this->_includeDir . self::CERTIFICATES_FOLDER_PATH);
                }
                copy($source, $this->_includeDir . self::CERTIFICATES_FOLDER_PATH . self::CERTIFICATE_NAME);
            } else if (strpos($source, self::CERTIFICATE_SANDBOX_NAME) !== false) {
                if (!file_exists($this->_includeDir . self::CERTIFICATES_FOLDER_PATH)) {
                    mkdir($this->_includeDir . self::CERTIFICATES_FOLDER_PATH);
                }
                copy($source, $this->_includeDir . self::CERTIFICATES_FOLDER_PATH . self::CERTIFICATE_SANDBOX_NAME);
            }
            if (strpos(str_replace($this->_includeDir, '', $target), '-')
                || !in_array(substr($source, strlen($source)-4, 4), array('.php'))) {
                return $this;
            }
            copy($source, $target);
        }
        return $this;
    }
}
