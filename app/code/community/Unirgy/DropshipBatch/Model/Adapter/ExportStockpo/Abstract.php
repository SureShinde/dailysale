<?php

abstract class Unirgy_DropshipBatch_Model_Adapter_ExportStockpo_Abstract extends Varien_Object
{
    public function init()
    {
        $this->setHasOutput(false);
        return $this;
    }

    abstract public function addPO($po);

    abstract public function renderOutput();

    public function preparePO($po)
    {
        $order = $po->getOrder();
        if (!$order->getEntityId()) {
            $order = Mage::getModel('sales/order')->load($po->getOrderId() ? $po->getOrderId() : $po->getParentId());
        }
        $this->setOrder($order);
        $this->setPo($po);
        $this->setStockPo($po->getStockPo());
        Mage::helper('udropship')->addVendorSkus($po);

        $vars = array(
            'order' => $this->getOrder(),
            'order_id' => $this->getOrder()->getIncrementId(),
            'billing' => $this->getOrder()->getBillingAddress(),
            'shipping' => $this->getOrder()->getShippingAddress(),
            'po' => $this->getPo(),
            'po_id' => $this->getPo()->getIncrementId(),
            'stockpo' => $this->getStockPo(),
            'stockpo_id' => $this->getStockPo()->getIncrementId(),
            'stock_vendor' => $this->getPo()->getStockVendor(),
            'vendor' => $this->getPo()->getVendor(),
        );
        Mage::dispatchEvent('udbatch_prepare_stockpo', array('vars'=>&$vars));
        $this->setVars($vars);

        return true;
    }

    public function preparePOItem($item)
    {
        if (!$this->getOrder()) {
            Mage::throwException('Order is not initialized');
        }

        $orderItem = $item->getOrderItem();
        if (!$orderItem) {
            if (!$item->getOrderItemId()) {
                $item = Mage::getModel('sales/order_shipment_item')->load($item->getEntityId());
            }
            $orderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
        }

        if (($orderItem->getChildren() || $orderItem->getChildrenItems() and $orderItem->getProductType() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
            || ($orderItem->getParentItem() and $orderItem->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
            || in_array($orderItem->getStatus(), $this->getBatch()->getSkipItemStatuses())) {
            return false;
        }

        $this->setOrderItem($orderItem);
        $this->setPoItem($item);

        $productOptions = $this->getOrderItem()->getProductOptions();
        $productOptionsArr = array();
        if (!empty($productOptions['options'])) {
            foreach ($productOptions['options'] as $o) {
                $productOptionsArr[] = $o['label'].': '.(!empty($o['print_value']) ? $o['print_value'] : $o['value']);
            }
        }
        if (!empty($productOptions['attributes_info'])) {
            foreach ($productOptions['attributes_info'] as $o) {
                $productOptionsArr[] = $o['label'].': '.$o['value'];
            }
        }
        if (!empty($productOptions['additional_options'])) {
            foreach ($productOptions['additional_options'] as $o) {
                $productOptionsArr[] = $o['label'].': '.(!empty($o['print_value']) ? $o['print_value'] : $o['value']);
            }
        }
        if ($orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $this->setOrigSku($item->getSku());
            $item->setSku($orderItem->getProductOptionByCode('simple_sku'));
            if ($item->getVendorSimpleSku()) {
                $this->setOrigVendorSku($item->getVendorSku());
                $item->setVendorSku($item->getVendorSimpleSku());
            }
        }
        $vars = array(
            'order' => $this->getOrder(),
            'order_id' => $this->getOrder()->getIncrementId(),
            'billing' => $this->getOrder()->getBillingAddress(),
            'shipping' => $this->getOrder()->getShippingAddress(),
            'po' => $this->getPo(),
            'po_id' => $this->getPo()->getIncrementId(),
            'stockpo' => $this->getStockPo(),
            'stockpo_id' => $this->getStockPo()->getIncrementId(),
            'stock_vendor' => $this->getPo()->getStockVendor(),
            'vendor' => $this->getPo()->getVendor(),

            'item' => $this->getPoItem(),
            'order_item' => $this->getOrderItem(),
            'product_options' => join('; ', $productOptionsArr),
        );
        Mage::dispatchEvent('udbatch_prepare_stockpo_item', array('vars'=>&$vars));
        $this->setVars($vars);

        return true;
    }

    public function restoreItem()
    {
        if ($this->getOrigSku()) {
            $this->getPoItem()->setSku($this->getOrigSku());
            $this->unsOrigSku();
        }
        if ($this->hasOrigVendorSku()) {
            $this->getPoItem()->setVendorSku($this->getOrigVendorSku());
            $this->unsOrigVendorSku();
        }
        return $this;
    }

    public function getTemplateFilter()
    {
        if (empty($this->_templateFilter)) {
            $this->_templateFilter = Mage::getModel('udbatch/templateFilter');
        }
        return $this->_templateFilter;
    }

    public function getExportTemplate()
    {
        if (!$this->hasData('export_template')) {
        	$exportTpl = $this->getBatch()->getVendor()->getBatchExportStockpoTemplate();
        	if (trim($exportTpl) == '') {
        		Mage::throwException(Mage::helper('udbatch')->__('Empty Export Template'));
        	}
            $this->setData('export_template', $exportTpl);
        }
        return $this->getData('export_template');
    }

    public function renderTemplate($text, $vars)
    {
        if (preg_match_all('#\[([a-z0-9._]+)\]#i', $text, $m, PREG_PATTERN_ORDER)) {
            $keys = array_unique($m[1]);
            $replaceFrom = array();
            $replaceTo = array();
            foreach ($keys as $key) {
                $keyArr = explode('.', $key);
                $value = $vars;
                foreach ($keyArr as $k) {
                    if (!isset($value[$k])) {
                        $value = '';
                        break;
                    }
                    $value = $value[$k];
                }

                $replaceFrom[] = '['.$key.']';
                $replaceTo[] = is_numeric($value) ? $value*1 : $value;
            }
            if (Mage::getStoreConfigFlag('udropship/batch/replace_nl2customchar')) {
                foreach ($replaceTo as &$_var) {
                    $_var = str_replace("\n", Mage::getStoreConfig('udropship/batch/replace_nl2customchar_value'), $_var);
                }
                unset($_var);

            }
            $text = str_replace($replaceFrom, $replaceTo, $text);
        }
        $text = $this->getTemplateFilter()->setVariables($vars)->filter($text);

        return $text;
    }

    public function __destruct()
    {
        $this->_data = array();
    }
}
