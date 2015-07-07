<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Fast Asynchronous Re-indexing
 * @version   1.1.6
 * @build     363
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


/**
 * Блок вывода потока (масива) сообщений,
 * которые пишутся во время работы модуля
 *
 * @category Mirasvit
 * @package  Mirasvit_AsyncIndex
 */
class Mirasvit_AsyncIndex_Block_Adminhtml_Panel_Stream extends Mage_Adminhtml_Block_Template
{
    protected $_stream     = null;
    protected $_splitQueue = null;

    public function _prepareLayout()
    {
        # start transaction (for use core_write connection)
        Mage::getSingleton('core/resource')->getConnection('core_write')->beginTransaction();

        $this->setTemplate('asyncindex/panel/stream.phtml');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        # end transaction
        Mage::getSingleton('core/resource')->getConnection('core_write')->commit();

        return $html;
    }

    /**
     * Возвращает подготовленный масив текущей очереди (5 елементов)
     *
     * @return array
     */
    public function getQueue()
    {
        $ts    = microtime(true);
        $queue = array();

        foreach ($this->getProcessCollection() as $process) {
            if ($process->getStatus() == Mirasvit_AsyncIndex_Model_Process::STATUS_WAIT) {
                $queue[] = 'Full reindex "'.$process->getIndexer()->getName().'"';
            }
        }

        $collection = $this->getIndexEventCollection();
        foreach ($collection as $event) {
            $status = 'new';
            if ($event->getId() == Mage::helper('asyncindex')->getVariable('current_event')) {
                $status = 'processing';
            }

            $event->setStatus($status);

            $queue[] = $event;
        }

        return $queue;
    }

    public function splitQueue()
    {
        if ($this->_splitQueue == null) {
            $this->_splitQueue = array();

            $processes = Mage::getSingleton('index/indexer')->getProcessesCollection();

            $collection = Mage::getModel('index/event')->getCollection()
                ->addProcessFilter($processes->getAllIds(), 'new');
            $collection->getSelect()
                ->group('main_table.entity')
                ->group('main_table.type')
                ->columns(array(
                    'cnt_event'         => 'COUNT(DISTINCT(main_table.event_id))',
                    'cnt_process_event' => 'COUNT(process_event.event_id)'
                ));

            foreach ($collection as $event) {
                $entity = $event->getEntity();
                $type   = $event->getType();

                $this->_splitQueue[$entity][$type]['events']    = $event->getCntEvent();
                $this->_splitQueue[$entity][$type]['processes'] = $event->getCntProcessEvent();
            }
        }

        return $this->_splitQueue;
    }

    public function getQueueSize()
    {
        $size = 0;
        foreach ($this->splitQueue() as $entity => $types) {
            foreach ($types as $type => $counts) {
                if ($entity == 'catalog_proudct' && $type == 'save') {
                    $size += $counts['events'] * 2;
                } else {
                    $size += $counts['events'];
                }
            }
        }

        return $size;
    }

    /**
     * Возвращает коллекцию текущей очереди
     *
     * @return object
     */
    public function getIndexEventCollection()
    {
        $processes = Mage::getSingleton('index/indexer')->getProcessesCollection();

        $collection = Mage::getModel('index/event')->getCollection()
            ->addProcessFilter($processes->getAllIds(), Mage_Index_Model_Process::EVENT_STATUS_NEW);
        $collection->getSelect()
            ->limit(5)
            ->group('entity_pk')
            ->group('entity')
            ->order('created_at asc');

        return $collection;
    }

    public function ucString($string)
    {
        $string = uc_words($string);
        $string = str_replace('_', ' ', $string);

        return $string;
    }

    /**
     * Возвращает масив сообщение (уже проиндексированные элементы)
     *
     * @return array
     */
    public function getStream()
    {
        if ($this->_stream == null) {
            $this->_stream = array();

            $collection = Mage::getModel('mstcore/logger')->getCollection()
                ->addFieldToFilter('module', 'AsyncIndex')
                ->setOrder('log_id', 'asc')
                ->setPageSize(1000);

            foreach ($collection as $log) {
                $info = @unserialize($log->getContent());

                $item = new Varien_Object();

                $item->setId($log->getId());
                $item->setTitle(@$info['text']);
                $item->setStatus(@$info['status']);
                $item->setChilds(new Varien_Data_Collection());

                if (!isset($info['finished_at'])) {
                    $info['finished_at'] = microtime(true);
                }

                $item->setProcessingTime(@$info['finished_at'] - @$info['created_at']);

                if (isset($info['message'])) {
                    $item->setMessage($info['message']);
                    $item->setTitle($item->getTitle().PHP_EOL.$item->getMessage());
                }

                if (@$info['status'] != 'start') {
                    $since = Mage::helper('asyncindex')->timeSince(microtime(true) - $info['finished_at']);
                    $item->setSince($since.' ago');
                }

                if (isset($info['parent_id']) && isset($this->_stream[$info['parent_id']])) {
                    $this->_stream[$info['parent_id']]->getChilds()->addItem($item);
                } else {
                    $this->_stream[$item->getId()] = $item;
                }
            }

            $this->_stream = array_reverse($this->_stream);
        }

        if ($this->getStatus() == 'waiting' || $this->getStatus() == 'success') {
            foreach ($this->_stream as $idx => $itm) {
                if ($this->_stream[$idx]->getStatus() == 'start') {
                    $this->_stream[$idx]->setStatus('error')
                        ->setProcessingTime(0);
                }
            }
        }

        return $this->_stream;
    }

    /**
     * Текущий статус модуля
     * success - очередь пуста
     * waiting - ждет запуска
     * processing - работает (обрабатывает очередь, реиндекс проверку)
     * error - произошла ошибка во время последнего запуска
     *
     * @return string
     */
    public function getStatus()
    {
        $helper = Mage::helper('asyncindex');
        $status = 'success';

        if ($this->getIndexEventCollection()->getSize() > 0) {
            $status = 'waiting';
        }

        if ($helper->isProcessing()) {
            $status = 'processing';
        }

        return $status;
    }

    public function getErrorMessage()
    {
        $job = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', 'asyncindex')
            ->addFieldToFilter('status', 'error')
            ->setOrder('scheduled_at', 'desc')
            ->getFirstItem();
        if ($job->getId()) {
            return $job->getMessages();
        }

        $log = Mage::getModel('mstcore/logger')->getCollection()
            ->addFieldToFilter('module', 'AsyncIndex')
            ->addFieldToFilter('level', 16)
            ->setOrder('created_at', 'desc')
            ->getFirstItem();
        if ($log->getId()) {
            return nl2br($log->getMessage()."\n".$log->getContent());
        }
    }

    /**
     * Сколько прошло времени с момента запуска обработки очереди
     *
     * @return string
     */
    public function getProcessingTime()
    {
        $startTime = Mage::helper('asyncindex')->getVariable('start_time');

        return Mage::helper('asyncindex')->timeSince(Mage::getSingleton('core/date')->gmtTimestamp() - $startTime);
    }

    /**
     * Коллекция индексов magento
     *
     * @return object
     */
    public function getProcessCollection()
    {
        return Mage::getModel('index/process')->getCollection();
    }

    public function getInvalidProductCount()
    {
        $cnt = Mage::helper('asyncindex')->getVariable('invalid_product_count');

        return intval($cnt);
    }

    public function getInvalidCategoryCount()
    {
        $cnt = Mage::helper('asyncindex')->getVariable('invalid_category_count');

        return intval($cnt);
    }
}