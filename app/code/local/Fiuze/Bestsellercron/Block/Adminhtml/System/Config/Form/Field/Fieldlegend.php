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
        $html .= '<b>"Category":</b> Choose a category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<b>"Search the store":</b> ';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'If disabled — you sort the products in a simple category by criteria. ';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'If checked — you get products from the whole store and show only setted amount of products (amount is setted in «Number of products»).';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'BUT: if you will check this checkbox not on a specific category (like Bestseller), but in simple category, this will uncheck all products from this category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<b>"Criteria": </b> By what criteria to sort products in this category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<b>"Time Period and Days Period":</b> For what time period orders should be taken.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<b>"History":</b>  Example: If we have set in Number of products 100, but we have only 5 orders in the set days period, we should use this column to go back in history and take products for set  number of days in this period. ';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'First displayed items - by Time Period and Days Period (in order of their sort), and lower - Days Back in history (in order of their sort).';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<b>"Task Schedule":</b>  Dynamic cron for each category. How often do you want to update information in your category.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<p><b>"Price filter": </b>';
        $html .= 'Price filter works for all the 3 criterias. It is filtering the products and showing those which correspond to the price range that we set. When you want to set a number equal to 5, you just type 5, when want a range, then type 5-10, when less than 5, then 0-5, if more, then 5-1000 or the most expensive price.</p>';
        $html .= '<p>So basically it will take into account days period, will check sales, take the products which let\'s say are in the price range of $5-10 and will sort them either by qty, revenue or max profit.</p>';
        $html .= '<b>"Number of products":</b> ';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'In "search the store" category - amount of products will be sorted by criteria (in the columns "Criteria", "Time Period", "Days Period", "History") and shown only chosen amount ("Number of products") on the Homepage.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'In simple category:';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'If checked (by default) — show all products in the category. ';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'If unchecked — you can set amount of products that should be shown in the category. If unchecked and empty — all products will be shown.';
        $html .= '</p>';
        $html .= '</p>';

        $html .= '</div>';
        return $html;
    }

}
