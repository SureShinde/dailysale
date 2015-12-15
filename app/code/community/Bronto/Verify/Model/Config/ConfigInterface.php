<?php

/**
 * Rewrite Conflict Checker Config Interface
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
interface Bronto_Verify_Model_Config_ConfigInterface
{
    /**
     * find all rewrites on XML node elements
     *
     * @param Bronto_Verify_Model_Core_Config_Element $config XML node
     *
     * @access public
     */
    public function getRewrites(Bronto_Verify_Model_Core_Config_Element $config);
}
