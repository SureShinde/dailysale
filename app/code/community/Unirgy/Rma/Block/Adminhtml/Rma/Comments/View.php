<?php

class Unirgy_Rma_Block_Adminhtml_Rma_Comments_View extends Mage_Adminhtml_Block_Sales_Order_Comments_View
{
    public function canSendCommentEmail()
    {
        return true;
    }
}