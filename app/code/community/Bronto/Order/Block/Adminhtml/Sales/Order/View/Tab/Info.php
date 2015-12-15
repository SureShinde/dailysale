<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
{
    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $order    = $this->getOrder();
        $orderRow = Mage::getModel('bronto_order/queue')->load($order->getId());

        $tid      = $orderRow->getBrontoTid();
        $imported = $orderRow->getBrontoImported();

        $deliveryHtml = empty($tid) ? $this->_getNoHtml() : $this->_getYesHtml();
        $importedHtml = empty($imported) ? $this->_getNoHtml() : $this->_getYesHtml();

        $html = parent::_toHtml();
        $html .= <<<SCRIPT
<script>
var orderTable, orderTableRow, orderTableCell;

// Add Delivery Row
// orderTableRow  = orderTable.insertRow(orderTable.rows.length);
// orderTableCell = orderTableRow.insertCell(0);
// orderTableCell.innerHTML = "<label>Associated with Bronto Delivery?</label>";
// orderTableCell = orderTableRow.insertCell(1);
// orderTableCell.innerHTML = "$deliveryHtml";

// Add Imported Row
// orderTableRow  = orderTable.insertRow(orderTable.rows.length);
// orderTableCell = orderTableRow.insertCell(0);
// orderTableCell.innerHTML = "<label>Imported into Bronto?</label>";
// orderTableCell = orderTableRow.insertCell(1);
// orderTableCell.innerHTML = "$importedHtml";
</script>
SCRIPT;

        return $html;
    }

    /**
     * @return string
     */
    private function _getYesHtml()
    {
        return 'YES';
    }

    /**
     * @return string
     */
    private function _getNoHtml()
    {
        return 'NO';
    }
}
