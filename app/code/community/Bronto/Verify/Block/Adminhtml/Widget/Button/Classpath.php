<?php

/**
 * Class Path Search Button
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Block_Adminhtml_Widget_Button_Classpath extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Search Class Path');
        $this->setOnClick("searchClasspath(); return false;");
    }
}