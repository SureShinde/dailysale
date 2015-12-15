<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Form_Caretip
    extends Bronto_Reviews_Block_Adminhtml_Reviews_Form_Abstract
{
    /**
     * @see parent
     */
    public function getPostType()
    {
        return Bronto_Reviews_Model_Post_Purchase::TYPE_CARETIP;
    }

    /**
     * @see parent
     */
    protected function _configureFieldset($fieldset, $post)
    {
        $defaultSend = $this->_helper->getPostPeriod($this->getPostType());
        $send = $this->_defaultOverride($fieldset, $post, array(
            'label' => $this->_helper->__('Send Period'),
            'note' => $this->_helper->__('Schedule the email this many days after the order status trigger for each care notification. Must be greater than or equal to 0.<br/><strong>Default</strong>: ' . $defaultSend),
            'name' => "period",
            'required' => true
        ));

        $defaultAdjust = $this->_helper->getPostAdjustment($this->getPostType());
        $adjustment = $this->_defaultOverride($fieldset, $post, array(
            'label' => $this->_helper->__('Adjustment Period'),
            'note' => $this->_helper->__('Adjust the send period by this many days.<br/><strong>Note</strong>: Negative numbers are allowed, and will <em>subtract</em> from the send period.<br/><strong>Default</strong>: '. $defaultAdjust),
            'name' => 'adjustment',
        ));

        $defaultSendLimit = $this->_helper->getPostSendLimit($this->getPostType());
        $sendLimit = $this->_defaultOverride($fieldset, $post, array(
            'label' => $this->_helper->__('Send Limit'),
            'note' => $this->_helper->__('Number of times the care notification can be scheduled per customer.<br/><strong>Note</strong>: -1 will always schedule.<br/><strong>Default</strong>: ' . $defaultSendLimit),
            'name' => "send_limit",
        ));

        $contentType = "{$this->getPostType()}_content";
        $content = $fieldset->addField($contentType, 'textarea', array(
            'label' => $this->_helper->__('Care Tip Content'),
            'name' => $contentType,
            'required' => true,
            'note' => $this->_helper->__('Care Tip Content should contain customer friendly information specific to this product. This information will be injected into the email via the <em>%%%%#extraContent%%%%</em> API tag and can contain HTML.')
        ));

        $this
            ->_dependsOnEnablement($send)
            ->_dependsOnEnablement($sendLimit)
            ->_dependsOnEnablement($adjustment)
            ->_dependsOnEnablement($content);
    }
}
