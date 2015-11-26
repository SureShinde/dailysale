<?php

class Bronto_Email_Block_Adminhtml_System_Email_Import_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    public function render(Varien_Object $row)
    {
        $actions = array();

        $actions[] = array(
            'url'     => $this->getUrl('*/*/edit', array('id' => $row->getId())),
            'caption' => $this->__('Edit'),
        );

        $stores = Mage::app()->getStores(true);
        if (is_array($stores) && count($stores) >= 1) {
            foreach ($stores as $store) {
                if (Mage::helper('bronto_email')->isEnabled('store', $store->getId())) {
                    $actions[] = array(
                        'url'     => $this->getUrl('*/*/massImport', array('template_id' => $row->getId(), 'store_id' => $store->getId())),
                        'caption' => $this->__('Import For Store: ' . (!$store->getId() ? 'Default' : $store->getName())),
                        'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to import the selected template?'),
                    );
                }
            }
        }
        $actions[] = array(
            'url'     => $this->getUrl('*/*/massDelete', array('template_id' => $row->getId(), 'delete_level' => 'full')),
            'caption' => $this->__('Delete'),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to delete the selected template?'),
        );

        $this->getColumn()->setActions($actions);

        return parent::render($row);
    }

    protected function _getEscapedValue($value)
    {
        return addcslashes(htmlspecialchars($value), '\\\'');
    }

    protected function _actionsToHtml(array $actions)
    {
        $html             = array();
        $attributesObject = new Varien_Object();
        foreach ($actions as $action) {
            $attributesObject->setData($action['@']);
            $html[] = '<a ' . $attributesObject->serialize() . '>' . $action['#'] . '</a>';
        }

        return implode(' <span class="separator">&nbsp;|&nbsp;</span> ', $html);
    }
}
