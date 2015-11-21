<?php
/**
 * Core
 *
 * @author      ahofs
 * @category    Fiuze
 * @package     Core
 * @copyright   Copyright (c) 2015 Fiuze
 */
class Fiuze_Core_Model_Design_Package extends Mage_Core_Model_Design_Package
{
    /**
     * Merge specified javascript files and return URL to the merged file on success
     * CORE BUG fix: Javascript content should be ended by ";" character.
     * Otherwise merging into one file does not work always.
     *
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $targetFilename = md5(implode(',', $files)) . '.js';
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false,
            function($file, $contents){return $contents.';';}, 'js'))
        {
            return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        }
        return '';
    }
}
