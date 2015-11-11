<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

/**
 * Feed logger
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Helper_FeedLogger
{
    const ERROR = 1;
    const TRACE = 2;
    const DEBUG = 3;

    const LOGGING_ENABLED = true;
    const WARNING_MEMORY_LIMIT = 75;
    const ERROR_MEMORY_LIMIT = 90;

    protected $startTime;
    protected $memoryLimit = null;
    protected $warningMemoryLimit = null;
    protected $errorMemoryLimit = null;

    protected $storeId = null;
    protected $logFile = null;

    /**
     *
     */
    public function __construct()
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');
        $this->startTime = $dataHelper->mtime();
        $this->setupMemoryLimits();
    }

    /**
     * Set store id
     *
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        $this->logFile = null;
    }

    /**
     * Error
     *
     * @param       $message
     * @param array $args
     */
    public function error($message, array $args = array())
    {
        $this->log($message, $args, self::ERROR);
    }

    /**
     * Trace
     *
     * @param       $message
     * @param array $args
     */
    public function trace($message, array $args = array())
    {
        $this->log($message, $args, self::TRACE);
    }

    /**
     * Debug
     *
     * @param       $message
     * @param array $args
     */
    public function debug($message, array $args = array())
    {
        $this->log($message, $args, self::DEBUG);
    }

    /**
     * Logs a message to the store log file with current execution time and memory usage
     *
     * Log level is an integer that specifies the type of logging message.
     * 1 is Error, higher levels are for debugging and tracing
     *
     * @param string $msg
     * @param array  $args
     * @param int    $logLevel
     *
     */
    protected function log($msg, array $args = array(), $logLevel = self::DEBUG)
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');
        $storeLogLevel = $dataHelper->getLogLevel($this->storeId);
        if ($logLevel && $storeLogLevel && $logLevel > $storeLogLevel) {
            return;
        }

        if (null === $this->logFile) {
            /** @var $feedHelper SLI_Search_Helper_Feed */
            $feedHelper = Mage::helper('sli_search/feed');
            $feedHelper->makeVarPath(array('log', 'sli'));
            $this->logFile = "sli" . DS . "sliFeedGen_{$this->storeId}.log";
        }

        $memoryUsage = memory_get_usage(true);
        $memoryUsageFormatted = $memoryUsage / 1024 / 1024 . "M";
        $percentage = "";
        $warningMessage = "";
        $memoryLimit = $this->memoryLimit;
        if ($memoryLimit != "-1") {
            $percentage = sprintf("%.0f", (($memoryUsage / $memoryLimit) * 100)) . "%";
            $warningMessage = $this->checkMemoryUsage($memoryUsage);
        }
        $time = sprintf("%.4fs", $dataHelper->mtime() - $this->startTime);
        Mage::log(
            "$time : $memoryUsageFormatted : $percentage $warningMessage-=- $msg", null, $this->logFile,
            static::LOGGING_ENABLED
        );
    }

    /**
     * Function to check what the memory usage is currently and how it is compared
     * to the set limits
     *
     * @param $currentUsage
     *
     * @return string
     */
    protected function checkMemoryUsage($currentUsage)
    {
        $message = '';
        if ($currentUsage > $this->warningMemoryLimit) {
            $message = "Warning - Using over " . self::WARNING_MEMORY_LIMIT . "% of php memory";
            if ($currentUsage > $this->errorMemoryLimit) {
                $message = "Error - Using over " . self::ERROR_MEMORY_LIMIT . "% of php memory: ";
            }
        }

        return $message;
    }

    /**
     * Setup the max php memory limit
     */
    protected function setupMemoryLimits()
    {
        $this->memoryLimit = ini_get('memory_limit');
        if (substr($this->memoryLimit, -1) == 'K') {
            $this->memoryLimit = str_replace('K', '', $this->memoryLimit) * 1024;
        } else {
            if (substr($this->memoryLimit, -1) == 'M') {
                $this->memoryLimit = str_replace('M', '', $this->memoryLimit) * 1024 * 1024;
            } else {
                if (substr($this->memoryLimit, -1) == 'G') {
                    $this->memoryLimit = str_replace('G', '', $this->memoryLimit) * 1024 * 1024 * 1024;
                }
            }
        }
        $this->warningMemoryLimit = $this->memoryLimit * (self::WARNING_MEMORY_LIMIT / 100);
        $this->errorMemoryLimit = $this->memoryLimit * (self::ERROR_MEMORY_LIMIT / 100);
    }
}
