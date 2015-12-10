<?php

/**
 * Permissionchecker Helper
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Helper_Permissionchecker
    extends Bronto_Verify_Helper_Data
{
    /**
     * Check file name to see if it matches anything that needs to be filtered
     *
     * @param $path
     *
     * @return bool
     */
    public function accept($path)
    {
        $exclusions   = Mage::getStoreConfig('bronto_verify/permissionchecker/exclude');
        $exclusions   = explode(',', $exclusions);
        $exclusions[] = '.';
        $exclusions[] = '..';
        array_walk($exclusions, create_function('&$val', '$val = trim($val);'));

        return !in_array($path, $exclusions);
    }
}