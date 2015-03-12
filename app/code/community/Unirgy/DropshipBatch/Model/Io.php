<?php

class Unirgy_DropshipBatch_Model_Io
{
    protected $_io = array();
    public function get($ioConfig, $batch=null)
    {
        $ioConfig = $this->_normalizeIoConfig($ioConfig, $batch);
        if (empty($ioConfig['scheme'])) {
            $ioType = 'file';
        } elseif (in_array($ioConfig['scheme'], array('ftps','ftp'))) {
            $ioConfig['passive'] = true;
            $ioType = 'ftp';
        } elseif ($ioConfig['scheme'] == 'sftp') {
            $ioType = 'sftp';
        } else {
            return false;
        }
        if (!isset($this->_io[$ioType])) {
            $this->_io[$ioType] = Mage::getModel('udbatch/io_'.$ioType);
        } else {
            $this->_io[$ioType]->close();
        }
        $this->_io[$ioType]->setUdbatch($batch);
        $this->_io[$ioType]->open($ioConfig);
        return $this->_io[$ioType];
    }

    public function filterLs($lsResult, $io)
    {
        $_lsResult = array();
        foreach ($lsResult as $r) {
            if ($io->getUdbatchGrep() && !fnmatch($io->getUdbatchGrep(), $r['text'])
                || $io->isDir($r['text'])
            ) {
                continue;
            }
            $_lsResult[] = $r;
        }
        $lsResult = $_lsResult;
        if ($io->getUdbatch() && ($dateFilter = $io->getUdbatch()->getDatetimeFilter())) {
            $filterFrom = null;
            if ('0000-00-00 00:00:00' != $dateFilter[0] && !empty($dateFilter[0])) {
                $filterFrom = new Zend_Date();
                $filterFrom->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $filterFrom->set($dateFilter[0], Varien_Date::DATETIME_INTERNAL_FORMAT);
                $filterFrom->setTimezone($dateFilter[2]);
            }
            $filterTo = null;
            if ('0000-00-00 00:00:00' != $dateFilter[1] && !empty($dateFilter[1])) {
                $filterTo = new Zend_Date();
                $filterTo->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $filterTo->set($dateFilter[1], Varien_Date::DATETIME_INTERNAL_FORMAT);
                $filterTo->setTimezone($dateFilter[2]);
            }
            $_lsResult = array();
            foreach ($lsResult as $r) {
                $filter = new Zend_Date();
                $filter->setTimezone($dateFilter[2]);
                $filter->set($io->mdtm($r['text']));
                if ((empty($filterFrom) || $filter->compare($filterFrom)>=0)
                    && (empty($filterTo) || $filter->compare($filterTo)==-1)
                ) {
                    $_lsResult[] = $r;
                }
            }
            $lsResult = $_lsResult;
        }
        return $lsResult;
    }

    protected function _normalizeIoConfig($ioConfig, $batch=null)
    {
        if (is_string($ioConfig)) {
            if (strpos($ioConfig, '://')===false || strpos($ioConfig, 'file:///')===0) {
                if (strpos($ioConfig, 'file://')) {
                    $ioConfig = substr($ioConfig, 7);
                }
                $dir = realpath(dirname($ioConfig));
                if (!(strpos($dir, realpath(Mage::getBaseDir('var')))===0
                    || strpos($dir, realpath(Mage::getBaseDir('media')))===0
                )) {
                    Mage::throwException(Mage::helper('udbatch')->__("Invalid local destination '%s'", $ioConfig));
                }
                $ioConfig = array(
                    'path' => $dir.DS.basename($ioConfig)
                );
            } else {
                $ioConfig = parse_url($ioConfig);
            }
        }
        if (empty($ioConfig['username'])) {
            $ioConfig['username'] = @$ioConfig['user'];
        }
        if (empty($ioConfig['user'])) {
            $ioConfig['user'] = @$ioConfig['username'];
        }
        if (empty($ioConfig['password'])) {
            $ioConfig['password'] = @$ioConfig['pass'];
        }
        if (empty($ioConfig['pass'])) {
            $ioConfig['pass'] = @$ioConfig['password'];
        }
        if (empty($ioConfig['scheme'])) {
        } elseif ($ioConfig['scheme'] == 'ftps') {
            $ioConfig['scheme'] = 'ftp';
            $ioConfig['ssl'] = true;
        } elseif ($ioConfig['scheme'] == 'sftp') {
            if (false === strpos($ioConfig['host'], ':')
                && !empty($ioConfig['port'])
            ) {
                $ioConfig['host'] = $ioConfig['host'].':'.$ioConfig['port'];
            }
        }
        if (!($batch && $batch->getLocationPathIsDirectory()) && !empty($ioConfig['path'])) {
            $ioConfig['grep'] = basename($ioConfig['path']);
            $ioConfig['path'] = dirname($ioConfig['path']);
        }
        return $ioConfig;
    }

}