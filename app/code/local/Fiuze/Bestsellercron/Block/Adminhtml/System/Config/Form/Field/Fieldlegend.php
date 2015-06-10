<?php

class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Fieldlegend
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';

        if ($element->getTooltip()) {
            $html .= '<td class="value with-tooltip">';
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="field-tooltip"><div>' . $element->getTooltip() . '</div></div>';
        } else {
            $html .= '<td class="value" style="width: auto">';
            $html .= $this->_getElementHtml($element);
        };

        $html.= '</td>';

        $html.= '</td>';

        return $html;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div>';
        $html .= '<p>';
        $html .= '<strong>Legend</strong>';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'First row is for category Bestseller. It has functionality that differs from other rows.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Category: Bestseller. This category is displayed in grid by default, it can\'t be deleted or changed. This category will contain amount of products that are set in the last column (Number of products).';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Criteria: By what criteria to sort products in this category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Time Period and Days Period: For what time period orders should be taken.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Task Shedule: Dynamic cron for each category. How often do you want to update information in your category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Time Back in history: Example: If we have set in Number of products 100, but we have only 5 orders, we should use this coloumn to go back in history and take products for setted number of days in this period.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'First displayed items - by Time Period and Days Period (in order of their sort), and lower - Days Back in history (in order of their sort).';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Number of products: This amount of products will be sorted by criterias (in the coloumns Criteria, Time Period, Days Period, Days Back in history) and shown only chosen amount (Number of products) on the Homepage.';
        $html .= '</p></br>';
        $html .= '<p>';
        $html .= 'Next rows should be added by user manually.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Category: In what category products should be sorted.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Criteria: By what criteria we want to sort products in chosen category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Time Period and Days Period: For what time period orders should be taken.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Task Shedule: Dynamic cron for each category. How often do you want to update information in your categories.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Time Back in history:';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'Number of products:';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'On the top of the page will be shown products by custom criteria. All the other products that have no orders will be shown down, but sorted by ID.';
        $html .= '</p>';
        $html .= '</div>';
        return $html;
    }

}
