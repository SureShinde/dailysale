<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
    /**
     * @see parent
     */
    public function setParentBlock(Mage_Core_Block_Abstract $block)
    {
        $block
            ->getChild('save_button')
            ->setOnClick("confirmAndSubmit();");
        return parent::setParentBlock($block);
    }

    /**
     * @see parent
     */
    public function _afterToHtml($html)
    {
        $html .= '
          <script type="text/javascript">
            function confirmAndSubmit() {
              var canSubmit = true;
              $$("#bronto_support_terms").each(function(elem) {
                if (elem.value === "0") {
                  canSubmit = confirm("You must agree to Bronto\'s Terms of Service in the Registration section. You agree to the Terms by pressing \"OK\".");
                  if (canSubmit) {
                    elem.value = "1";
                  }
                }
              });
              if (canSubmit) {
                configForm.submit();
              }
            }
          </script>';
        return parent::_afterToHtml($html);
    }
}
