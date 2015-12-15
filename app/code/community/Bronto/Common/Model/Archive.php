<?php

class Bronto_Common_Model_Archive
{

    /**
     * @var string
     */
    protected $_filename;

    /**
     * @var ZipArchive
     */
    protected $_zip;

    /**
     * @var boolean
     */
    protected $_isOpen = false;

    /**
     * Get the archive filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * Sets the underlying zip
     *
     * @param ZipArchive $zip
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setZip(ZipArchive $zip)
    {
        if ($this->_isOpen) {
            throw new InvalidArgumentException("Archiver is already open.");
        }
        $this->_zip = $zip;

        return $this;
    }

    /**
     * @return ZipArchive
     */
    public function getZip()
    {
        if (is_null($this->_zip)) {
            $this->setZip(new ZipArchive);
        }

        return $this->_zip;
    }


    /**
     * Forwards calls to proxy object
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        $return = call_user_func_array(array($this->getZip(), $name), $args);
        switch ($name) {
            case 'close':
                $this->_isOpen = false;
                break;
            case 'open':
                $this->_filename = $this->getZip()->filename;
                $this->_isOpen   = $return === true;
            default:
                return $return;
        }

        return $return;
    }
}
