<?php

/**
 * Config Conflict Checker
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Config_Checker
    extends Bronto_Verify_Model_Config_ConfigAbstract
{
    /**
     * Get the conflicts
     *
     * @param Bronto_Verify_Model_Core_Config_Element $config Parameter description (if any) ...
     *
     * @return array
     * @access public
     */
    public function getConflicts(
        Bronto_Verify_Model_Core_Config_Element $config
    )
    {
        $rewrites = $this->getRewrites($config);
        foreach ($rewrites as $type => $modules) {
            foreach ($modules as $module => $classes) {
                foreach ($classes as $class => $conflicts) {
                    if (count($classes[$class]) > 1) {
                        echo "$type : $module : $class is rewrite multiple times by";
                    }
                }
            }
        }

        return $this->getRewrites($config);
    }
}
