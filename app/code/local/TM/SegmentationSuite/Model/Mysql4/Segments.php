<?php

class TM_SegmentationSuite_Model_Mysql4_Segments extends Mage_CatalogRule_Model_Resource_Rule
{
    protected $_productIds = null;

    protected $_productCollection = null;

    protected function _construct()
    {
        $this->_init('segmentationsuite/segments', 'segment_id');
    }

    public function getOptionArray()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
        ;
        $rowset = array();
        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            $rowset[] = array('label' => $row['segment_title'], 'value' => $row['segment_id']);
        }

        return $rowset;
    }

    public function load(Mage_Core_Model_Abstract $object, $value, $field=null)
    {
        if (!intval($value) && is_string($value)) {
            $field = 'identifier'; // You probably don't have an identifier...
        }
        return parent::load($object, $value, $field);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
	        ->from($this->getTable('segmentationsuite/store'))
	        ->where('segment_id = ?', $object->getId());

        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $storesArray = array();
            foreach ($data as $row) {
                $storesArray[] = $row['store_id'];
            }
            $object->setData('store_id', $storesArray);
        }

        return parent::_afterLoad($object);
    }

    public function deleteSegmentIndex($segmentId)
    {
        try {
            $deleteWhere = $this->_getWriteAdapter()->quoteInto('segment_id = ?', $segmentId);

            /* delete segment indexs */
            $this->_getWriteAdapter()->delete(
                $this->getTable('segmentationsuite/index'), $deleteWhere);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addSegmentStoreIds($object)
    {
        $deleteWhere = $this->_getWriteAdapter()->quoteInto('segment_id = ?', $object->getId());

        /* store_view */
        $this->_getWriteAdapter()->delete($this->getTable('segmentationsuite/store'), $deleteWhere);
        foreach ($object->getData('stores') as $storeId) {
            $data = array('segment_id' => $object -> getId(), 'store_id' => $storeId);
            $this -> _getWriteAdapter() -> insert($this -> getTable('segmentationsuite/store'), $data);
        }
        return $this;
    }

    public function loadStoreIds($object) {
        $rulesId = $object -> getId();
        $storeIds = array();
        if ($rulesId) {
            $storeIds = $this -> lookupStoreIds($rulesId);
        }
        $object->setStoreId($storeIds);
    }

    /**
     * Get store ids, to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id) {
        $select = $this->_getReadAdapter()->select()->from(
            $this->getTable('segmentationsuite/store'), 'store_id'
        )->where('segment_id = ?', $id);

        $result = $this->_getReadAdapter()->fetchCol($select);

        return $result;
    }

    /**
     * Update customers which are matched for rule
     *
     * @param Mage_CatalogRule_Model_Rule $rule
     * @return Mage_CatalogRule_Model_Resource_Rule
     */
    public function updateSegmentCustomerData($segment, $customerIds)
    {
        $segmentId = $segment->getId();
        //$this->deleteSegmentIndex($segmentId);
        $write = $this -> _getWriteAdapter();
        $write -> beginTransaction();

        $rows = array();

        try {
            foreach ($customerIds as $customerId) {
                $rows[] = array('segment_id' => $segmentId, 'entity_id' => $customerId, );
                if (count($rows) == 1000) {
                    $write -> insertMultiple($this -> getTable('segmentationsuite/index'), $rows);
                    $rows = array();
                }
            }
            if (!empty($rows)) {
                $write -> insertMultiple($this -> getTable('segmentationsuite/index'), $rows);
            }
            $write -> commit();
        } catch (Exception $e) {
            $write -> rollback();
            throw $e;
        }
        return $this;
    }

    public function indexSegment($count = 1, $step = 0)
    {
        $connection = $this -> _getReadAdapter();
        $labelSelect = $connection->select()
            ->from(array('ss' => $this -> getTable('segmentationsuite/segments')), 'segment_id')
            ->order('segment_id')
            -> limit($count, $count * $step);

        $result = $connection -> fetchCol($labelSelect);
        return $result;
    }
}
