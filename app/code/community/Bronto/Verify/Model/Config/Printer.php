<?php

/**
 * Datastore printer
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Config_Printer
{
    /**
     * Rewrite printer
     *
     * @param Bronto_Verify_Model_Config_Datastore $datastore Datastore to print from
     * @param string                               $title     Title to print
     *
     * @return string
     * @access public
     */
    public function render(
        Bronto_Verify_Model_Config_Datastore $datastore,
        $title
    )
    {
        $block = Mage::app()->getLayout()->createBlock('bronto_verify/conflictprinter');
        $block->setRewrites($datastore->getRewriteConflicts());
        $block->setTitle($title);

        return $block->toHtml();
    }
}
