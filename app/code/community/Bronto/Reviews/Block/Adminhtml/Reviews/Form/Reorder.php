<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Form_Reorder
    extends Bronto_Reviews_Block_Adminhtml_Reviews_Form_Abstract
{
    /**
     * @see parent
     */
    public function getPostType()
    {
        return Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER;
    }

    /**
     * @see parent
     */
    protected function _configureFieldset($fieldset, $post)
    {
        $defaultSend = $this->_helper->getPostPeriod($this->getPostType());
        $send = $this->_defaultOverride($fieldset, $post, array(
            'label' => $this->_helper->__('Send Period'),
            'note' => $this->_helper->__('Schedule the email this many days after the order status trigger for each reorder reminder. Must be greater than or equal to 0.<br/><strong>Default</strong>: ' . $defaultSend),
            'name' => 'period',
            'required' => true
        ));

        $options = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();
        $defaultMultiplier = $this->_helper->getPostMultiplier();
        $defaultLabel = null;
        foreach ($options as $option) {
            if ($option['value'] == $defaultMultiplier) {
                $defaultLabel = $option['label'];
                break;
            }
        }

        $multiplier = $this->_defaultOverride($fieldset, $post, array(
          'type' => 'select',
          'values' => $options,
          'label' => $this->_helper->__('Send Period Per Unit'),
          'note' => $this->_helper->__('If <em>Yes</em>, the Send Period will be multiplied by the quantity ordered.<br><strong>Default</strong>: ' . $defaultLabel),
          'name' => 'multiply_by_qty',
          'required' => true,
        ));

        $defaultAdjust = $this->_helper->getPostAdjustment($this->getPostType());
        $adjustment = $this->_defaultOverride($fieldset, $post, array(
            'label' => $this->_helper->__('Adjustment Period'),
            'note' => $this->_helper->__('Adjust the send period by this many days.<br/><strong>Note</strong>: Negative numbers are allowed, and will <em>substract</em> from the send period.<br/><strong>Default</strong>: '. $defaultAdjust),
            'name' => 'adjustment',
        ));

        $contentType = "{$this->getPostType()}_content";
        $content = $fieldset->addField($contentType, 'textarea', array(
            'label' => $this->_helper->__('Extra Content'),
            'name' => $contentType,
            'note' => $this->_helper->__('Extra Content should contain anything extra specific to this reordered product. This value is optional, and will be injected into the scheduled email via <em>%%%%#extraContent%%%%</em> API tag and can contain HTML')
        ));

        $this
            ->_dependsOnEnablement($send)
            ->_dependsOnEnablement($multiplier)
            ->_dependsOnEnablement($adjustment)
            ->_dependsOnEnablement($content);
    }
}
