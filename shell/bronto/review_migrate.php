<?php

// Can either be run from shell dir, modman, or cron
if (file_exists('abstract.php')) {
    require_once 'abstract.php';
} else if (preg_match('/\.modman/', dirname(__FILE__))) {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/shell/abstract.php';
} else {
    require_once dirname(dirname(dirname(__FILE__))) . '/shell/abstract.php';
}

class Bronto_Reviews_Migration_Script extends Mage_Shell_Abstract
{
    const MAX_STEP = 50;

    private $_apis = array();
    private $_staging = array();
    private $_messages = array();
    private $_realRun = false;

    public function usageHelp()
    {
        return <<<USAGE
Usage: php -f bronto/review_migrate.php --run
USAGE;
    }

    protected function _prepareCollections()
    {
        foreach (Mage::app()->getStores() as $store) {
            $this->_staging[$store->getId()] = array();
            $this->_apis[$store->getId()] = Mage::helper('bronto_common')
                ->getApi(null, 'store', $store->getId());
        }
    }

    protected function _backfillLogs($storeId)
    {
        echo "Backfilling logs for store {$storeId}\n";
        $api = $this->_apis[$storeId];
        if ($api) {
            $deliveryObject = $api->getDeliveryObject();
            $messageObject = $api->getMessageObject();
            $deliveryFilter = array('id' => array(), 'status' => 'unsent');
            $lookupTable = array();
            foreach ($this->_staging[$storeId] as $tuple) {
                list($order, $entry) = $tuple;
                $lookupTable[$entry->getDeliveryId()] = $order;
                $deliveryFilter['id'][] = $entry->getDeliveryId();
                if ($this->_realRun) {
                    $entry->delete();
                }
            }
            if (!empty($lookupTable)) {
                $actualNumbers = 0;
                foreach ($deliveryObject->readAll($deliveryFilter, false, false)->iterate() as $row) {
                    if (array_key_exists($row->messageId, $this->_messages)) {
                        $message = $this->_messages[$row->messageId];
                    } else {
                        $message = $messageObject->createRow();
                        $message->id = $row->messageId;
                        $message->read();
                    }
                    $order = $lookupTable[$row->id];
                    $log = Mage::getModel('bronto_reviews/log')
                        ->setDeliveryDate($row->start)
                        ->setDeliveryId($row->id)
                        ->setMessageId($row->messageId)
                        ->setMessageName($message->name)
                        ->setCustomerEmail($order->getCustomerEmail())
                        ->setOrderId($order->getId())
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setPostName(Mage::helper('bronto_reviews')->getPostLabel('settings'))
                        ->setStoreId($order->getStoreId())
                        ->setFields(serialize(array()));
                    if ($this->_realRun) {
                        $log->save();
                    }
                    $actualNumbers++;
                }
                echo " - Successfully filled {$actualNumbers} entries.\n";
            }
        }
        unset($this->_staging[$storeId]);
        $this->_staging[$storeId] = array();
    }

    public function run()
    {
        try {
            $this->_realRun = $this->getArg('run') || $this->getArg('r');
            if (!$this->_realRun) {
                echo "Starting dry run...\n";
            }
            $this->_prepareCollections();
            $entries = Mage::getModel('bronto_reviews/queue')->getCollection();
            foreach ($entries as $entry) {
                $order = Mage::getModel('sales/order')->load($entry->getOrderId());
                if (!$order->getId()) {
                    continue;
                }
                $this->_staging[$order->getStoreId()][] = array($order, $entry);
                if (count($this->_staging[$order->getStoreId()]) == self::MAX_STEP) {
                    $this->_backfillLogs($order->getStoreId());
                }
            }
            foreach ($this->_staging as $storeId => $tuples) {
                $this->_backfillLogs($storeId);
            }
        } catch (Exception $e) {
            echo "No migration necessary...\n";
        }
        echo "Shutting down.\n";
    }
}

$shell = new Bronto_Reviews_Migration_Script();
$shell->run();
