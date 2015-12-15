<?php

class Bronto_Product_Model_Filter
{
    protected $_content = '';
    protected $_fields = array();

    /**
     * Set the original content
     *
     * @param string $content
     * @return Bronto_Product_Model_Filter
     */
    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * Set the related fields for this filter
     *
     * @param array $fields
     * @return Bronto_Product_Model_Filter
     */
    public function setFields($fields)
    {
        $this->_fields = $fields;
        return $this;
    }

    /**
     * Process the related api tags for this content
     *
     * @return string
     */
    public function process()
    {
        list($content, $blocks) = $this->gatherDynamicBlocks($this->_content);
        foreach ($blocks as $key => $block) {
            $content = str_replace($key, $this->processBlock($block), $content);
        }
        return $content;
    }

    /**
     * Splits the given content into blocks
     *
     * @param string $content
     * @return tuple (string, array)
     */
    protected function gatherDynamicBlocks($content)
    {
        $blocks = array();
        $pattern = '|\{dynamic_code\}\s*\{[^/]+\}\s*(.+?)\{/.+?\}\s*\{/dynamic_code\}|s';
        if (preg_match_all($pattern, $content, $matches)) {
            $blockIndex = 0;
            foreach ($matches[0] as $match) {
                $content = str_replace($match, "block_{$blockIndex}_", $content);
                $blockIndex++;
            }
            $blockIndex = 0;
            foreach ($matches[1] as $match) {
                $blocks["block_{$blockIndex}_"] = $match;
                $blockIndex++;
            }
        }
        return array($content, $blocks);
    }

    /**
     * Transforms the dynamic block with the related content
     *
     * @param string $block
     * @return string
     */
    protected function processBlock($block)
    {
        $content = '';
        foreach ($this->_fields as $key => $fields) {
            $index = $key + 1;
            $currentBlock = $block;
            foreach ($fields as $field) {
                list($name, $num) = explode('_', $field['name']);
                $currentBlock = preg_replace("/%%#{$name}_#%%/", $field['content'], $currentBlock);
            }
            $content .= $currentBlock;
        }
        if (empty($content)) {
            $content = $block;
        }
        // Close the loop
        $content = preg_replace("/%%#.+?_#%%/", '', $content);
        return $content;
    }
}
