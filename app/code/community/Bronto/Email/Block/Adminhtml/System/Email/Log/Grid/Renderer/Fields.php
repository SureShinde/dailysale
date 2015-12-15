<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Log_Grid_Renderer_Fields extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $fields = array();
        try {
            $fields = unserialize($row->getFields());
        } catch (Exception $e) {
            //
        }

        if (empty($fields)) {
            return '';
        }

        $fieldsHtml = '<dl style="margin-top: 10px">';
        foreach ($fields as $field) {
            $fieldsHtml .= '<dt><strong>' . $field['name'] . '</strong></dt>';
            $fieldsHtml .= '
                <dd style="padding-left: 15px; padding-bottom: 10px">
                    <pre>' . htmlspecialchars($field['content']) . '</pre>
                </dd>
            ';
        }
        $fieldsHtml .= '</dl>';

        return '
            <a href="#" onclick="javascript:document.getElementById(\'fields-' . $row->getId() . '\').style.display = \'block\';">Show</a>
            <span style="color:#666">|</span>
            <a href="#" onclick="javascript:document.getElementById(\'fields-' . $row->getId() . '\').style.display = \'none\';">Hide</a>
            <div id="fields-' . $row->getId() . '" style="display: none">' . $fieldsHtml . '</div>
        ';
    }
}
