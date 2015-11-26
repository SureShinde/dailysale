<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

/**
 * Minigrid system config field element type
 * Displays the minigid in a usable backend fashion. Requires a source
 * model to properly display;
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Block_System_Config_Form_Field_Minigrid extends Varien_Data_Form_Element_Abstract
{
    /**
     * Add the js block which contains the js that makes
     * the mini grids work only once to the admin js
     * block
     */
    protected function addJsIfNecessary()
    {
        $alias = 'ba.minigrid.js';
        $block = Mage::app()->getLayout()
            ->createBlock('core/template', $alias)
            ->setTemplate('sli/search/sysconfig/ba_minigrid_js.phtml');

        $js = Mage::app()->getLayout()->getBlock('js');
        if (!$js->getChild($alias)) {
            Mage::app()->getLayout()->getBlock('js')->append($block, $alias);
        }
    }

    /**
     * Default values of field array. Field array defines
     * the fields on the grid.
     *
     * @return array
     */
    protected function getDefaultSourceValues()
    {
        return array(
            "{$this->getLabel()}" => array("width" => "98%", "type" => "text"),
        );
    }

    /**
     * Element rendererd html. Called by getDefaultHtml which combines
     * the label html with this html to render the full element. Specifically,
     * this is the html to render the field input
     *
     * NOTE: We are breaking php runtime to fall into html directly
     * in this function instead of using a template to keep this module
     * succinct, contained, and to avoid rendering html/js via strings
     * as is the convention for form and form element renderers.
     *
     * Providing optional parameters for required inputs if this block is being
     * used outside of its normal use case (such as outside of system config)
     *
     * @param string $tableId
     * @param string $fieldName
     * @param array  $fields
     * @param array  $rowData
     *
     * @return string
     */
    public function getElementHtml($tableId = null, $fieldName = null, $fields = array(), $rowData = array())
    {
        $this->addJsIfNecessary();

        $label = $this->getLabel();
        $tableId = ($tableId) ? $tableId : $this->getHtmlId();
        $fieldName = ($fieldName) ? $fieldName : $this->getName();

        $fields = (empty($fields)) ? $this->getValues() : $fields;
        if (!$fields) {
            $fields = $this->getDefaultSourceValues();
        }
        $rowData = (empty($rowData)) ? $this->getValue() : $rowData;
        if (!is_array($rowData)) {
            $rowData = array();
        }

        ob_start();
        ?>
        <div class="grid">
            <table id="ba-<?php echo $tableId ?>-table" class="option-header" cellpadding="0" cellspacing="0">
                <thead>
                <tr class="headings">
                    <?php foreach ($fields as $header => $def) : ?>
                        <th style="width:<?php echo $def['width'] ?>"><?php echo ucwords(
                                str_replace("_", " ", $header)
                            ) ?></th>
                    <?php endforeach; ?>
                    <th style="width:30px">Remove</th>
                </tr>
                </thead>
                <tbody id="ba-<?php echo $tableId ?>">
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="<?php echo count($fields) + 1 ?>" class="a-right">
                        <button id="ba-add-<?php echo $tableId ?>" class="scalable add" type="button">
                            <span>Add <?php echo ucwords($label) ?></span></button>
                    </td>
                </tr>
                </tfoot>
            </table>
            <input type="text" class="validate-minigrid-attributes" style="opacity:0" value="">
        </div>
        <style>
            select.validation-failed {
                border: 1px solid red;
            }
        </style>
        <script type="text/javascript">
            Event.observe(window, 'load', function () {
                (new baMiniGrid()).init($("ba-<?php echo $tableId?>"),
                    $('ba-add-<?php echo $tableId?>'),
                    "<?php echo $fieldName?>",
                    <?php echo json_encode($rowData)?>,
                    <?php echo json_encode($fields)?>);
            });
        </script>
        <?php
        return ob_get_clean();
    }
}
