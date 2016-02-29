<?php
 /**
  */
class SFC_Kount_Model_Source_WorkflowMode
{

    const MODE_PRE_AUTH = 'pre_auth';
    const MODE_POST_AUTH = 'post_auth';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::MODE_PRE_AUTH,
                'label' => Mage::helper('kount')->__('Pre-Authorization Payment Review')
            ),
            array(
                'value' => self::MODE_POST_AUTH,
                'label' => Mage::helper('kount')->__('Post-Authorization Payment Review')
            )
        );
    }
    
}
