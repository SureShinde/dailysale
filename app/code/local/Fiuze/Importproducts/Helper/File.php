<?php
/**
 * Import File Helper
 *
 * @author Mihail
 */
class Fiuze_Importproducts_Helper_File extends Mage_Core_Helper_Abstract
{
    /**
     * Media path to extension files
     *
     * @var string
     */
    const MEDIA_PATH    = 'import';

    /**
     * Maximum size for file in bytes
     * Default value is 1M
     *
     * @var int
     */
    const MAX_FILE_SIZE = 1048576;

    /**
     * Array of allowed file extensions
     *
     * @var array
     */
    protected $_allowedExtensions = array('xml');

    /**
     * Return the base media directory for imported files
     *
     * @return string
     */
    public function getBaseDir()
    {
        return Mage::getBaseDir('media') . DS . self::MEDIA_PATH;
    }

    /**
     * Return the Base URL for the imported files
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return Mage::getBaseUrl('media') . '/' . self::MEDIA_PATH;
    }

    /**
     * Remove file by filename
     *
     * @param string $importFile
     * @return bool
     */
    public function removeFile($importFile)
    {
        $io = new Varien_Io_File();
        $io->open(array('path' => $this->getBaseDir()));
        if ($io->fileExists($importFile)) {
            return $io->rm($importFile);
        }
        return false;
    }

    /**
     * Upload file and return uploaded file name or false
     *
     * @throws Mage_Core_Exception
     * @param string $scope the request key for file
     * @return array| file content or false
     */
    public function uploadFile($scope)
    {
        $adapter  = new Zend_File_Transfer_Adapter_Http();
        $adapter->addValidator('Size', true, self::MAX_FILE_SIZE);
        if ($adapter->isUploaded($scope)) {
            // validate xml file
            if (!$adapter->isValid($scope)) {
                Mage::throwException(Mage::helper('fiuze_importproducts')->__('Uploaded file is not valid'));
            }
            $upload = new Varien_File_Uploader($scope);
            $upload->setAllowCreateFolders(true);
            $upload->setAllowedExtensions($this->_allowedExtensions);
            $upload->setAllowRenameFiles(true);
            $upload->setFilesDispersion(false);
            if ($upload->save($this->getBaseDir())) {
                //return $upload->getUploadedFileName();
                return $this->loadFile($upload->getUploadedFileName());
            }
        }
        return false;
    }


    /**
     * Removes folder with cached files
     *
     * @return boolean
     */
    public function flushFilesCache()
    {
        $cacheDir  = $this->getBaseDir() . DS . 'cache' . DS ;
        $io = new Varien_Io_File();
        if ($io->fileExists($cacheDir, false) ) {
            return $io->rmdir($cacheDir, true);
        }
        return true;
    }
    
    /**
     * Loads the xml file.
     *
     * @return array|xml nodes
     */
    public function loadFile($xmlPath) 
    {
        $xmlObj = new Varien_Simplexml_Config($this->getBaseDir() . '/' . $xmlPath);
        $xmlData = $xmlObj->getNode();   
        
        return $xmlData; 
    }
}
