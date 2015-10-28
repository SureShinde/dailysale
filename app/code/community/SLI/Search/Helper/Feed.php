<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license – please visit www.sli-systems.com/LSC for full license details.
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
 * Feed helper for multifunctional feed related utilities
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Helper_Feed
{
    const AJAX_NOTICE = "Currently generating feeds...";

    protected $feedFilePath = null;

    /**
     * These attributes should always be added to the collection
     *
     * @var array
     */
    protected $defaultRequiredAttributes
        = array(
            'product_id',
            'sku',
            'name',
            'type_id',
            'is_salable',
            'url_path',
            'url_key',
            'product_id',
            'special_from_date',
            'special_price',
            'special_to_date',
            'category_ids',
            'visibility',
        );

    /**
     * Get ajax message.
     *
     * @return string
     */
    public function getAjaxNotice()
    {
        return static::AJAX_NOTICE;
    }

    /**
     * Returns the feed file path
     *
     * @return string
     */
    public function getFeedFilePath()
    {
        if ($this->feedFilePath === null) {
            $this->feedFilePath = $this->makeVarPath(array('sli', 'feeds'));
        }

        return $this->feedFilePath;
    }

    /**
     * Returns the feed file name for the given store id and type.
     *
     * @param $storeId
     *
     * @return string
     */
    public function getFeedFileName($storeId)
    {
        return "sliCatalogFeed_{$storeId}.xml";
    }

    /**
     * Returns the full/absolute feed file name for the given store id and type.
     *
     * @param $storeId
     *
     * @return string
     */
    public function getFeedFile($storeId)
    {
        return $this->getFeedFilePath() . DS . $this->getFeedFileName($storeId);
    }

    /**
     * Create path within var folder if necessary given an array
     * of directory names
     *
     * @param array $directories
     *
     * @return string
     */
    public function makeVarPath($directories)
    {
        $path = Mage::getBaseDir('var');
        foreach ($directories as $dir) {
            $path .= DS . $dir;
            if (!is_dir($path)) {
                mkdir($path);
                chmod($path, 0777);
            }
        }

        return $path;
    }

    /**
     * Checks whether or not there are feed generation locks currently in place.
     *
     * @return boolean
     */
    public function feedLocksExist()
    {
        $path = $this->getFeedFilePath();
        foreach (scandir($path) as $fileName) {
            $file = $path . DS . $fileName;
            if (is_file($file) && !is_dir($file) && false !== strpos($fileName, '.lock')) {
                $modificationTime = filemtime($file);
                if (!$modificationTime) {
                    return true;
                } else {
                    if ((time() - filemtime($file)) > (24 * 60 * 60)) { // Check if older than 1 day
                        unlink($file); // Lock file older than a day so remove it

                        return false;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Lock feed processing.
     *
     * @param int $storeId
     *
     * @return string
     */
    public function lockFeedProcessing($storeId = 0)
    {
        $lockFile = $this->getFeedFile($storeId);
        file_put_contents($lockFile . ".lock", 'locked');
        @chmod($lockFile, 0666);
    }

    /**
     * Unlock feed processing.
     */
    public function unlockFeedGeneration()
    {
        $path = $this->getFeedFilePath();
        foreach (scandir($path) as $fileName) {
            $file = $path . DS . $fileName;
            if (is_file($file) && !is_dir($file) && false !== strpos($fileName, '.lock')) {
                unlink($file);
            }
        }
    }

    /**
     * Checks  write permissions on feed files that exist and
     * the permissions on the folder that they are located in
     *
     * @throws SLI_Search_Exception
     */
    public function checkWritePermissions()
    {
        $path = $this->getFeedFilePath();

        if (!(is_writable($path))) {
            throw new SLI_Search_Exception("Unable to write feed location: " . $path);
        }

        foreach (scandir($path) as $fileName) {
            $file = $path . DS . $fileName;
            if (is_file($file) && !(is_writable($file))) {
                throw new SLI_Search_Exception("Unable to write/update feed file: " . $file);
            }
        }
    }

    /**
     * Back ups previous feeds for a given store id.
     *
     * Back up previous feed into backup directory, previous backup
     * into archive, and remove the last archive.
     *
     * @param $storeId
     */
    public function backUpFeeds($storeId)
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        if (!$dataHelper->isBackupFeed()) {
            return;
        }

        $file = $this->getFeedFileName($storeId);
        $backupTwo = $this->makeVarPath(array('sli', 'backups', 'bak.bak'));
        $backupOne = $this->makeVarPath(array('sli', 'backups', 'bak'));
        $path = $this->getFeedFilePath();

        if (is_file($backupTwo . DS . $file)) {
            unlink($backupTwo . DS . $file);
        }
        if (is_file($backupOne . DS . $file)) {
            rename($backupOne . DS . $file, $backupTwo . DS . $file);
            chmod($backupTwo . DS . $file, 0666);
        }
        if (is_file($path . DS . $file)) {
            copy($path . DS . $file, $backupOne . DS . $file);
            chmod($backupOne . DS . $file, 0666);
        }
    }

    /**
     * Returns a list of default attributes.
     *
     * @return array
     */
    public function getDefaultRequiredAttributes()
    {
        return $this->defaultRequiredAttributes;
    }

    /**
     * Returns array of attribute names.
     *
     * @return array
     */
    public function getExtraAttributes()
    {
        $attributes = $this->getDefaultRequiredAttributes();

        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        $attributeLines = $dataHelper->getAttributes();
        foreach ($attributeLines as $attributeLine) {
            if (isset($attributeLine['attribute']) && !in_array($attributeLine['attribute'], $attributes)) {
                // just attribute names
                $attributes[] = $attributeLine['attribute'];
            }
        }

        return array_unique($attributes);
    }
}