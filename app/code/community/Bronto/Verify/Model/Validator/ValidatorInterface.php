<?php

/**
 * Validator Interface
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
interface Bronto_Verify_Model_Validator_ValidatorInterface
{
    /**
     * Validate business logic for chain of responsibility nodes
     *
     * @param SplFileInfo $file     File node to check
     * @param array       $badFiles existing bad files to report on
     *
     * @access public
     */
    public function validateSetting(SplFileInfo $file, array $badFiles);
}
