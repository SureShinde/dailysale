<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Block_Adminhtml_SystemConfigFormField_Autoinvoice extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::render($element);
        $html .=<<<EOT
<script type="text/javascript">
document.observe("dom:loaded", function() {
    $('{$element->getHtmlId()}').observe('change', function(e){
        var el = e.element()
        sync_autoinvoice_fields(el)
    })
    sync_autoinvoice_fields($('{$element->getHtmlId()}'))
})
function sync_autoinvoice_fields(el) {
    var stEl = $(el.id+'_statuses')
    if (el.value==0) {
        for (var i=0; i<stEl.options.length; i++) {
            if (stEl.options[i].selected) {
                stEl.options[i].selected=false;
            }
        }
        stEl.disable()
    } else {
        stEl.enable()
    }
}
</script>
EOT;
        return $html;
    }
}