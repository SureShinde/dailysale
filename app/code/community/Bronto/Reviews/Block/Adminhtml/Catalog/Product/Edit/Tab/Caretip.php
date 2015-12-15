<?php

class Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Caretip
    extends Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Abstract
{
    /**
     * Gets the post purchase type
     *
     * @return string
     */
    public function getPostType()
    {
        return Bronto_Reviews_Model_Post_Purchase::TYPE_CARETIP;
    }
}
