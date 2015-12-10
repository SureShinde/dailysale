<?php

/**
 * Locator factory
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Path_Locator_Factory
{
    /**
     * Get path locator implementation based on PHP version
     *
     * @return Bronto_Verify_Model_Path_Locator_LocatorInterface
     * @access public
     */
    public function getLocator()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $model = new Bronto_Verify_Model_Path_Locator_Stack(new SplStack());
        } else {
            $model = new Bronto_Verify_Model_Path_Locator_Array(array());
        }

        return $model;
    }
}
