<?php

class Bronto_Order_Model_Container_Redemption extends Enterprise_PageCache_Model_Container_Abstract
{
    /**
     * @see parent
     */
    protected function _getCacheId()
    {
        return 'BRONTO_ORDER_REDEMPTION_' . $this->_getIdentifier() . '_' . rand(0, 99);
    }

    /**
     * @see parent
     */
    protected function _renderBlock()
    {
        $blockClass = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');
        $block = new $blockClass;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());
        return $block->toHtml();
    }

    /**
     * @see parent
     */
    protected function _getIdentifier()
    {
        return microtime();
    }

    /**
     * @see parent
     */
    protected function _saveCache($data, $id, $tags = array(), $lifetime = null)
    {
        return false;
    }
}
