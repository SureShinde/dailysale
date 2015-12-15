<?php

/**
 * Datastore Config Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Model_Config_Datastore
    extends Mage_Core_Model_Abstract
{
    /**
     * data store
     *
     * @var array
     * @access protected
     */
    protected $_store = array();

    /**
     * store rewrite
     *
     * @param string      $oldValue   node name being overwritten
     * @param string      $newValue   node name that is being set to current
     * @param string|null $configFile (optional) Config file with rewrite
     * @param string|null $path       (optional) path to node in XML
     *
     * @return void
     * @access public
     */
    public function addRewrite(
        $oldValue,
        $newValue,
        $configFile = 'Unavailable',
        $path = 'Unavailable'
    )
    {
        if ('Unavailable' != $configFile) {
            //  +1 just removes the starting '/' from the path
            $configFile = substr($configFile, strlen(Mage::getBaseDir()) + 1, strlen($configFile));
        }
        $this->_store[] = array(
            'oldValue' => $oldValue,
            'newValue' => $newValue,
            'file'     => $configFile,
            'path'     => $path
        );
    }

    /**
     * Get the datastore
     *
     * @return array
     * @access public
     */
    public function getRewriteConflicts()
    {
        return $this->_store;
    }
}
