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
            'note' => $this->_helper->__('Schedule the email this many days, per unit, after the order status trigger for each reorder reminder. Must be greater than or equal to 0.<br/><strong>Default</strong>: ' . $defaultSend),
            'name' => 'period',
            'required' => true
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
            ->_dependsOnEnablement($adjustment)
            ->_dependsOnEnablement($content);
    }
}
