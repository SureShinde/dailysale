<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Log_Grid_Renderer_Customer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     *
     * @return mixed
     */
    public function render(Varien_Object $row)
    {
        if ($row->getCustomerId() && Mage::getSingleton('admin/session')->isAllowed('customer/manage')) {
            $customerEditUrl = $this->getUrl('*/customer/edit', array('id' => $row->getCustomerId()));

            return sprintf(
                '<a href="%s">%s</a>',
                $customerEditUrl,
                parent::render($row)
            );
        }

        return parent::render($row);
    }
}
