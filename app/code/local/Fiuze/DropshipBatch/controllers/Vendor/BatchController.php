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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
require_once(Mage::getModuleDir('controllers','Unirgy_DropshipBatch').DS.'Vendor/BatchController.php');

class Fiuze_DropshipBatch_Vendor_BatchController extends Unirgy_DropshipBatch_Vendor_BatchController
{
    public function importOrdersPostAction(){
        $r = $this->getRequest();
        $hlp = Mage::helper('udropship');
        $bHlp = Mage::helper('udbatch');
        try {
            $r->setParam('vendor_id', $this->_getSession()->getVendor()->getId());
            $r->setParam('batch_type', 'import_orders');
            $bHlp->processPost();
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            if ($bHlp->getBatch()) {
                $this->_getSession()->addError(
                    $bHlp->getBatch()->getErrorInfo($e->getMessage())
                );
            }
        }
        if ($bHlp->getBatch() != null && $bHlp->getBatch()->getStatus() == 'success') {
            $this->_getSession()->addSuccess($hlp->__('Processed %s import rows', $bHlp->getBatch()->getNumRows()));
        }
        $this->_redirect('udbatch/vendor_batch/importOrders');
    }
}