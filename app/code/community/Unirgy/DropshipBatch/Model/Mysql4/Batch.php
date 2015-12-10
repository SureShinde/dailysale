<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipBatch
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipBatch_Model_Mysql4_Batch extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('udbatch/batch', 'batch_id');
    }

    public function _afterSave(Mage_Core_Model_Abstract $object)
    {
        parent::_afterSave($object);
        $this->flushRowsLog($object);
    }

    public function flushRowsLog(Mage_Core_Model_Abstract $object)
    {
        if ($object->getRowsLog()) {
        	if (in_array($object->getBatchType(), array('import_inventory', 'export_inventory'))) {
            	$table = $this->getTable('batch_invrow');
        	} else {
        		$table = $this->getTable('batch_row');
        	}
            $id = $object->getId();
            foreach ($object->getRowsLog() as $l) {
                $l['batch_id'] = $id;
                if (is_array($l['row_json'])) {
                    $l['row_json'] = Zend_Json::encode($l['row_json']);
                }
                $this->_getWriteAdapter()->insert($table, $l);
            }
            $object->unsRowsLog();
        }
    }
}