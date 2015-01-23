<?php

class Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Reorder
    extends Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Abstract
{
    /**
     * @see parent
     */
    public function getPostType()
    {
        return Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER;
    }
}
