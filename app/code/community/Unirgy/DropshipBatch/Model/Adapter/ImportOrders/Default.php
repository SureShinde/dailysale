<?php

class Unirgy_DropshipBatch_Model_Adapter_ImportOrders_Default extends Unirgy_DropshipBatch_Model_Adapter_ImportOrders_Abstract
{
    public function init()
    {}
    public function parse($content)
    {
        $hlp = Mage::helper('udbatch');

        $_content = preg_split("/\r\n|\n\r|\r|\n/", $content);
        if ($_content !== false) {
            $content = implode("\n", $_content);
        }

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $content);
        rewind($fp);

        $fields = $this->getImportFields();
        $rows = array();
        $idx = 0;
        while (!feof($fp)) {
            $r = @fgetcsv($fp, 0, $this->getVendor()->getBatchImportOrdersFieldDelimiter(), '"');
            if (!$idx++ && $this->getVendor()->getBatchImportOrdersSkipHeader()) continue;
            if (!$r) {
                $rows[] = array('error'=>$hlp->__('Invalid row format'));
                continue;
            }
            $row = array();
            foreach ($r as $i=>$v) {
                if (isset($fields[$i])) {
                    $row[$fields[$i]] = trim($v);
                }
            }
            $rows[] = $row;
        }
        fclose($fp);

        return $rows;
    }

    public function initImportFields()
    {
        return $this->_initImportFields(true);
    }
    protected $_initImportFields;
    protected function _initImportFields($refresh=false)
    {
        if (is_null($this->_initImportFields) || $refresh) {
            $tpl = $this->getVendor()->getBatchImportOrdersTemplate();
            $this->setData('import_template', $tpl);
            $this->getBatch()->setData('import_template', $tpl);
            if (!preg_match_all('#\[([^]]+)\]([^[]+)?#', $tpl, $m, PREG_PATTERN_ORDER)) {
                Mage::throwException('Invalid import template');
            }
            if (!in_array('po_id', $m[1]) && !in_array('order_id', $m[1]) || !in_array('tracking_id', $m[1])) {
                Mage::throwException('Missing required field');
            }
            if (in_array('po_id', $m[1]) && in_array('order_id', $m[1])) {
                Mage::throwException('Either po_id OR order_id can be specified, but not both');
            }
            $this->setData('import_fields', $m[1]);
            $this->getBatch()->setData('import_fields', $m[1]);
            $this->setData('import_delimiter', $m[2][0]);
            $this->getBatch()->setData('import_delimiter', $m[2][0]);
            $this->_initImportFields = true;
        }
        return $this;
    }

    public function getImportFields()
    {
        $this->_initImportFields();
        return $this->getData('import_fields');
    }

    protected function _validateRows(&$rows)
    {
        $hlp = Mage::helper('udbatch');
        $allowDupTrackIds = false;
        $poIds = array();
        $orderIds = array();
        $trackIds = array();
        foreach ($rows as $i=>&$r) {
            if (!empty($r['error'])) {
                continue;
            }
            if (empty($r['po_id']) && empty($r['order_id']) || empty($r['tracking_id'])) {
                $r['error'] = $hlp->__('Missing required field');
                continue;
            }
            if (!empty($trackIds[$r['tracking_id']])) {
                $r['error'] = $hlp->__('Duplicate tracking_id within file');
                continue;
            }
            if (!empty($r['po_id'])) {
                $poIds[$r['po_id']] = $i;
            } else {
                $orderIds[$r['order_id']] = $i;
            }
            $trackIds[$r['tracking_id']] = $i;
        }
        unset($r);

        if ($orderIds) {
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('increment_id', array('in'=>array_keys($orderIds)));
            if ($orders->count()) {
                // @see http://groups.google.com/group/magento-devel/browse_thread/thread/d7afe64c5da94b27
                #$oIds = $orders->getAllIds();
                $oIds = array();
                foreach ($orders as $o) {
                    $oIds[$o->getId()] = $o->getIncrementId();
                }
                $pos = Mage::getModel('udropship/po')->getCollection()
                    ->addFieldToFilter('order_id', array('in'=>array_keys($oIds)))
                    ->addFieldToFilter('udropship_vendor', $this->getVendorId());
                foreach ($pos as $po) {
                    $poIds[$po->getIncrementId()] = $orderIds[$oIds[$po->getOrderId()]];
                }
            }
        }

        foreach ($rows as $i=>&$r) {
            if (!in_array($i, $poIds)) {
                $r['error'] = $hlp->__('Invalid Order or PO ID');
            }
            if (empty($r['po_id'])) {
                $r['po_id'] = array_search($i, $poIds);
            }
        }
        unset($r);

        // find already existing tracking_ids
        $tracks = Mage::getModel('sales/order_shipment_track')->getCollection()
            ->addAttributeToFilter(Mage::helper('udropship')->trackNumberField(), array('in'=>array_keys($trackIds)));
        foreach ($tracks as $t) {
            $i = $trackIds[$t->getNumber()];
            $poId = $this->getBatch()->getPoIncIdFromTrack($t);
            $r =& $rows[$i];
            if (!$allowDupTrackIds || isset($r['po_id']) && $r['po_id']==$poId) {
                $r['error'] = $hlp->__('Duplicate tracking_id');
                $r['track_id'] = $t->getId();
                #unset($poIds[$poId]);
            }
        }
        unset($r);

        return $poIds;
    }

    public function process($rows)
    {
        $markAsShipped = in_array(
            $this->getVendor()->getData('batch_import_orders_po_status'),
            $this->getBatch()->getMarkAsShippedStatuses()
        );

        $hlp = Mage::helper('udbatch');
        $allowDupTrackIds = false;

        Mage::dispatchEvent(
            'udbatch_import_orders_convert_before',
            array('batch'=>$this->getBatch(), 'adapter'=>$this, 'vars'=>array('rows'=>&$rows))
        );

        $poIds = $this->_validateRows($rows);

        // find POs and orders
        $pos = Mage::getModel('udropship/po')->getCollection()
            ->addAttributeToFilter('udropship_vendor', $this->getVendorId())
            ->addAttributeToFilter('increment_id', array('in'=>array_keys($poIds)))
            ->addOrders();

        $carriers = $this->getCarriers();
        foreach ($pos as $po) {
            $order = $po->getOrder();

            try {
                $r = $rows[$poIds[$po->getIncrementId()]];
                if (empty($r['error'])) {
                    $method = explode('_', $po->getUdropshipMethod(), 2);
                    $carrier = !empty($r['carrier']) ? $r['carrier'] : 'custom';
                    $title = !empty($r['title']) ? $r['title'] : (isset($carriers[$carrier]) ? $carriers[$carrier] : $method[0]);
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($r['tracking_id'])
                        ->setCarrierCode($carrier)
                        ->setTitle($title)
                        ->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_READY);
                    if (isset($r['shipping_date']) && !empty($r['shipping_date'])) {
                        $track->setCreatedAt($r['shipping_date']);
                        $track->setData('__update_date', 1);
                    }
                    $this->getBatch()->processTrack($po, $track, $markAsShipped);
                }
            } catch (Exception $e) {
                $r['error'] = $hlp->__($e->getMessage());
            }
            $this->getBatch()->addImportRowLog($order, $po, $r, @$track);
        }

        return $this;
    }

    protected $_carriers;
    public function getCarriers()
    {
        if ($this->_carriers===null) {
            $carriers = array();
            $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();
            $carriers['custom'] = Mage::helper('sales')->__('Custom Value');
            foreach ($carrierInstances as $code => $carrier) {
                if ($carrier->isTrackingAvailable()) {
                    $carriers[$code] = $carrier->getConfigData('title');
                }
            }
            $this->_carriers = $carriers;
        }
        return $this->_carriers;
    }

}
