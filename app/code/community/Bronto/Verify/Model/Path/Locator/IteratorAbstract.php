<?php

/**
 * Locator Iterator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
abstract class Bronto_Verify_Model_Path_Locator_IteratorAbstract
{
    /**
     * Locator implementation
     *
     * @var Bronto_Verify_Model_Path_Locator_LocatorInterface
     * @access protected
     */
    protected $_iterator = null;

    /**
     * Constructor
     *
     * @param Bronto_Verify_Model_Path_Locator_LocatorInterface $iterator
     */
    public function __construct($iterator)
    {
        $this->_iterator = $iterator;
    }
}
