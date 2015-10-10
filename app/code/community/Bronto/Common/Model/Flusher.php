<?php

class Bronto_Common_Model_Flusher implements Bronto_Api_Write_Flusher
{
    private $_result;
    private $_helper;

    public function __construct()
    {
        $this->_result = new Bronto_Object(array(
            'success' => 0,
            'error' => 0,
            'total' => 0
        ));
    }

    /**
     * @see parent
     */
    public function onFlush($iterator)
    {
        $now = Mage::getSingleton('core/date')->gmtDate();
        try {
            foreach ($iterator as $result) {
                $queueRow = $this->_queueEntry($result->getOriginal()->getQueueRow());
                $item = $result->getItem();
                if ($item->getIsError()) {
                    $queueRow
                        ->setBrontoImported(null)
                        ->setBrontoSuppressed("{$item->getErrorCode()}: {$item->getErrorString()}");
                    $this->_result->incrementError();
                } else {
                    $queueRow
                        ->setBrontoImported($now)
                        ->setBrontoSuppressed(null);
                    $this->_result->incrementSuccess();
                }
                $queueRow->save();
                $this->_result->incrementTotal();
            }
            $this->_flushLogs($iterator->getOperation()->getApi());
        } catch (InvalidArgumentException $iae) {
            Mage::helper($this->_helper)->writeDebug("Client error: {$iae->getMessage()}");
        } catch (Exception $e) {
            $request = $iterator->getRequest();
            $requestData = $request->getData();
            $objects = $requestData[$request->getKey()];
            switch ($e->getCode()) {
            case 107:
                foreach ($objects as $object) {
                    if (is_array($object) && !($object instanceof Bronto_Object)) {
                        $object = new Bronto_Object($object);
                    }
                    $queueRow = $this->_queueEntry($object->getQueueRow());
                    try {
                        $iterator->getOperation()->getApi()->execute(new Bronto_Object(array(
                            'method' => $request->getMethod(),
                            'data' => array($request->getKey() => array($object->toArray())),
                            'hasUpdates' => true
                        )));
                        $queueRow
                            ->setBrontoImported($now)
                            ->setBrontoSuppressed(null);
                        $this->_result->incrementSuccess();
                    } catch (Exception $e) {
                        $queueRow
                            ->setBrontoImported(null)
                            ->setBrontoSuppressed("Failed to process contact.");
                        $this->_result->incrementError();
                    }
                    $queueRow->save();
                    $this->_result->incrementTotal();
                    $this->_flushLogs($iterator->getOperation()->getApi());
                }
                break;
            default:
                if ($e->getCode() > 200) {
                    foreach ($objects as $object) {
                        if (is_array($object) && !($object instanceof Bronto_Object)) {
                            $object = new Bronto_Object($object);
                        }
                        $this->_queueEntry($object->getQueueRow())
                            ->setBrontoImported(null)
                            ->setBrontoSuppressed($e->getMessage())
                            ->save();
                        $this->_result->incrementError();
                        $this->_result->incrementTotal();
                    }
                }
                Mage::helper($this->_helper)->writeError($e);
                $this->_flushLogs($iterator->getOperation()->getApi());
            }
        }
    }

    /**
     * Extracts entry and returns the original queue model
     *
     * @return mixed
     */
    protected function _queueEntry($originalRow)
    {
        $queueRow = Mage::getModel("{$this->_helper}/queue");
        $queueRow->setData($originalRow);
        return $queueRow;
    }

    /**
     * Verbose log the API flush
     *
     * @param Bronto_Api $api
     */
    protected function _flushLogs($api)
    {
        $helper = Mage::helper($this->_helper);
        $apiLog = "{$this->_helper}_api.log";
        $helper->writeVerboseDebug("===== FLUSH =====", $apiLog);
        $helper->writeVerboseDebug(var_export($api->getLastRequest(), true), $apiLog);
        $helper->writeVerboseDebug(var_export($api->getLastResponse(), true), $apiLog);
    }

    /**
     * Gets the cached results from the consequtive write calls
     *
     * @return array
     */
    public function getResult()
    {
        return $this->_result->toArray();
    }

    /**
     * @return
     */
    public function setHelper($helperName)
    {
        $this->_helper = $helperName;
        return $this;
    }
}
