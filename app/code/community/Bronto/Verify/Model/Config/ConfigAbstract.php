<?php

/**
 * Rewrite Conflict Checker Config Abstract
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
abstract class Bronto_Verify_Model_Config_ConfigAbstract
    extends Mage_Core_Model_Abstract
    implements Bronto_Verify_Model_Config_ConfigInterface
{
    /**
     * Chain of Responsibility link
     *
     * @var object
     * @access protected
     */
    protected $_nextHandler = null;

    /**
     * psuedo constructor
     *
     * If a handler is passed into the constructor then set it as the next link
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        if (isset($this->_data[0])) {
            $this->_nextHandler = $this->_data[0];
        }
    }

    /**
     * Check if there are more handlers and if so get the rewrites from them
     *
     * @param Bronto_Verify_Model_Core_Config_Element $config   XML node
     * @param array                                   $rewrites existing rewrites
     *
     * @return array  rewrites
     * @access public
     */
    public function getRewrites(
        Bronto_Verify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        if (!is_null($this->_nextHandler)) {
            return $this->_nextHandler->getRewrites($config, $rewrites);
        } else {
            return $rewrites;
        }
    }

    /**
     * Find if XML node has any rewrites and if so append them into list
     *
     * @param Bronto_Verify_Model_Core_Config_Element $config    XML Node
     * @param array                                   &$rewrites existing rewrites
     *
     * @return void
     * @access protected
     */
    protected function _findRewrites(
        Bronto_Verify_Model_Core_Config_Element $config,
        &$rewrites = array()
    )
    {
        $reflect = new ReflectionObject($config);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $module  = $prop->getName();
            $reflect = new ReflectionObject($config->$module);
            if ($reflect->hasProperty('rewrite')) {
                $rewrite    = new ReflectionObject($config->$module->rewrite);
                $properties = $rewrite->getProperties(ReflectionProperty::IS_PUBLIC);
                foreach ($properties as $property) {
                    $class = $property->name;
                    $rewrites[$this->_type][$module][$class][]
                           = (string)$config->$module->rewrite->$class;
                }
            }
        }
    }
}
