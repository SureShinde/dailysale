<?php

class TM_SegmentationSuite_Adminhtml_Segmentationsuite_SegmentsController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_productCount = 1000;

    protected $_processTime = 29;

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('templates_master/segmentationsuite/segments')
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Templates Master'), Mage::helper('segmentationsuite')->__('Templates Master'))
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Segmentation Suite'), Mage::helper('segmentationsuite')->__('Segmentation Suite'))
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Customer Segments'), Mage::helper('segmentationsuite')->__('Customer Segments'));
        return $this;
    }

    /**
     * Banner list page
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('segmentationsuite/adminhtml_segments'));
        $this->renderLayout();
    }

    /**
     * Create new banner
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Banner edit form
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('segmentationsuite/segments');
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('segmentationsuite')->__('This segment no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = Mage::getSingleton('adminhtml/session')->getPageData(true);

        if (!empty($data)) {
            $model->addData($data);
        }
        $model->getConditions()->setJsFormObject('segments_conditions_fieldset');

        Mage::register('segmentationsuite_segments', $model);

        $this->loadLayout(array('default', 'editor'))
            ->_setActiveMenu('templates_master/segmentationsuite/segments')
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Templates Master'), Mage::helper('segmentationsuite')->__('Templates Master'))
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Segmentation Suite'), Mage::helper('segmentationsuite')->__('Segmentation Suite'))
            ->_addBreadcrumb(Mage::helper('segmentationsuite')->__('Customer Segments'), Mage::helper('segmentationsuite')->__('Customer Segments'));

        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true)
            ->addItem('js', 'tm/adminhtml/tabs.js');

        $this
            ->_addBreadcrumb($id ? Mage::helper('segmentationsuite')->__('Edit Segment') : Mage::helper('segmentationsuite')->__('New Segment'), $id ? Mage::helper('segmentationsuite')->__('Edit Segment') : Mage::helper('segmentationsuite')->__('New Segment'))
            ->_addContent(
                $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit')
                    ->setData('action', $this->getUrl('*/*/save'))
                    ->setData('form_action_url', $this->getUrl('*/*/save'))
            )
            ->_addLeft($this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tabs'))
            ->renderLayout();
    }

    /**
     * Banner grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_grid')->toHtml()
        );
    }

    /**
     * Save label
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            try {

                $model = Mage::getModel('segmentationsuite/segments');
                if (array_key_exists('segment_id', $data)) {
                    $model->load($data['segment_id']);
                } else {
                  $model->setId(null);
                }
                $data['conditions'] = $data['rule']['conditions'];
                if (isset($data['rule'])) {
                    unset($data['rule']);
                }

                if ((int)$data['segment_status'] == '0') {
                    $indexModel = Mage::getModel('segmentationsuite/index');
                    $indexModel->deleteDisableIndex($data['segment_id']);
                }
                $model->loadPost($data);
                Mage::getSingleton('adminhtml/session')->setPageData($model->getData());

                $model->save();
                $this->getRequest()->setParam('segment_id', $model->getId());
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('segmentationsuite')->__('Segment was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setPageData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                if (array_key_exists('segment_id', $data)) {
                    $this->_redirect('*/*/edit', array('id' => $data['segment_id']));
                } else {
                    $this->_redirect('*/*/edit');
                }

                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('segmentationsuite/segments');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('segmentationsuite')->__('Segment was successfully deleted')
                );
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('segmentationsuite')->__('Unable to find a segment to delete')
        );
        $this->_redirect('*/*/');
    }

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('segmentationsuite/segments'))
            ->setPrefix('conditions');

        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function chooserAction()
    {
        $block = false;
        switch ($this->getRequest()->getParam('attribute')) {
            case 'product_ids':
                $block = $this->getLayout()->createBlock(
                    'segmentationsuite/adminhtml_widget_chooser_product', 'segmentationsuite_widget_chooser_product',
                    array('js_form_object' => $this->getRequest()->getParam('form'),
                ));
                break;

            case 'category_ids':
                $block = $this->getLayout()->createBlock(
                        array('js_form_object' => $this->getRequest()->getParam('form'))
                    )->setCategoryIds($this->getRequest()->getParam('selected', array()));
                break;
        }
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * Get tree node (Ajax version)
     */
    public function categoriesJsonAction()
    {
        if ($categoryId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Initialize category object in registry
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory()
    {
        $categoryId = (int) $this->getRequest()->getParam('id',false);

        $storeId    = (int) $this->getRequest()->getParam('store');

        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    $this->_redirect('*/*/', array('_current'=>true, 'id'=>null));
                    return false;
                }
            }
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);
        return $category;
    }

   public function applyRulesAction()
   {
       try {
           $timeStart = time();
           $segmentModel = Mage::getResourceModel('segmentationsuite/segments');

           if ($this->getRequest()->getParam('clear_session')) {
               $obj = new Varien_Object();
               Mage::getSingleton('adminhtml/session')->setData('segments_object', $obj);
               $segmentModel->deleteAllLabelIndex();
               $obj->setQueryStep(0);
               $obj->setProcessed(0);
               $obj->setProductCount($this->_productCount);
           } else {
               $obj = Mage::getSingleton('adminhtml/session')->getData('segments_object');
               $queryOffset = $obj->getQueryStep();
           }

           if ($obj->getReindexData()) {
               $result = $this->processRelations($timeStart);
               return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                   'completed' => false,
                   'message'   => Mage::helper('segmentationsuite')->__(
                       '%d segments(s) reindexed', $obj->getProcessed()
                   )
               )));
           }
           $reindexData = $segmentModel->getItemsToProcess($obj->getProductCount(), $obj->getQueryStep());

           $obj->addData(array(
               'reindex_data'      => $reindexData,
               'query_step'        => $obj->getQueryStep(),
               'order_offset'      => 0,
           ));

           if ($obj->getReindexData()) {

               $result = $this->processRelations($timeStart);
               return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                   'completed' => false,
                   'message'   => Mage::helper('segmentationsuite')->__(
                       '%d segment(s) reindexed', $obj->getProcessed()
                   )
               )));
           }

           Mage::getSingleton('adminhtml/session')->unsetData('segments_object');
           Mage::getSingleton('adminhtml/session')->addSuccess(
               Mage::helper('segmentationsuite')->__(
                   '%d segment(s) was successfully reindexed', $obj->getProcessed()
                )
           );
           $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
               'processed' => $obj->getProcessed(),
               'completed' => true,
               'message'   => Mage::helper('segmentationsuite')->__(
                   '%d product(s) reindexed', $obj->getProcessed()
               )
           )));
       } catch (Exception $e){
           Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
       }
   }

   public function processRelations($timeStart)
   {
       $obj = Mage::getSingleton('adminhtml/session')->getData('segments_object');
       $reindexData = $obj->getReindexData();

       $segmentModel = Mage::getResourceModel('segmentationsuite/segments');
       $ruleModel = Mage::getModel('segmentationsuite/segments');
       for ($i = $obj->getOrderOffset(); $i < count($reindexData); $i++) {
           $ruleModel->load($reindexData[$i]);
           if (1 == $ruleModel->getSegmentStatus()) {
               $segmentModel->updateSegmentCustomerData($ruleModel);
           }

           $obj->setProcessed($obj->getProcessed() + 1);
           $obj->setOrderOffset($i + 1);
           return array(
               'processed' => $i + 1,
               'completed' => false
           );
       }
       $obj->setQueryStep($obj->getQueryStep() + 1);
       $obj->setReindexData(array());
       return array(
           'processed' => $i + 1,
           'completed' => true
       );
   }

//    public function saveSystemRuleData($data)
//    {
//        if ($data) {
//            try {
//                $model = Mage::getModel('prolabels/label');
//
//                if ((int)$data['label_status'] == 0) {
//                    $indexModel = Mage::getModel('prolabels/index');
//                    $indexModel->deleteDisableIndex($data['rules_id']);
//                }
//                $model->loadPost($data);
//                $model->save();
//                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('prolabels')->__('Label was successfully saved'));
//
//                return;
//            } catch (Exception $e) {
//                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//                Mage::getSingleton('adminhtml/session')->setPageData($data);
//                $this->_redirect('*/*/edit', array('id' => $data['rules_id']));
//                return;
//            }
//        }
//    }
//
//    public function applySystemRuleAction()
//    {
//        try {
//            $timeStart = time();
//            $labelModel = Mage::getResourceModel('prolabels/label');
//
//            if ($this->getRequest()->getParam('clear_session')) {
//                $this->saveSystemRuleData($this->getRequest()->getParams());
//                $obj = new Varien_Object();
//                Mage::getSingleton('adminhtml/session')->setData('prolabels_object', $obj);
//                $indexModel = Mage::getResourceModel('prolabels/index');
//                $indexModel->deleteIndexs($this->getRequest()->getParam('rules_id'));
//                $obj->setQueryStep(0);
//                $obj->setProcessed(0);
//                $obj->setProductCount($this->_productCount);
//                $obj->setLabelId($this->getRequest()->getParam('rules_id'));
//            } else {
//                $obj = Mage::getSingleton('adminhtml/session')->getData('prolabels_object');
//                $queryOffset = $obj->getQueryStep();
//            }
//
//            if ($obj->getReindexData()) {
//                $result = $this->processRelations($timeStart);
//                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
//                    'completed' => false,
//                    'message'   => Mage::helper('prolabels')->__(
//                        '%d customer(s) reindexed', $obj->getProcessed()
//                    )
//                )));
//            }
//            $reindexData = $labelModel->getItemsToProcess($obj->getProductCount(), $obj->getQueryStep());
//
//            $obj->addData(array(
//                'reindex_data'      => $reindexData,
//                'query_step'        => $obj->getQueryStep(),
//                'order_offset'      => 0,
//            ));
//
//            if ($obj->getReindexData()) {
//
//                $result = $this->systemProcessRelations($timeStart);
//                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
//                    'completed' => false,
//                    'message'   => Mage::helper('prolabels')->__(
//                        '%d product(s) reindexed', $obj->getProcessed()
//                    )
//                )));
//            }
//
//            Mage::getSingleton('adminhtml/session')->unsetData('prolabels_object');
//            Mage::getSingleton('adminhtml/session')->addSuccess(
//                Mage::helper('prolabels')->__(
//                    '%d product(s) was successfully reindexed', $obj->getProcessed()
//                 )
//            );
//            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
//                'processed' => $obj->getProcessed(),
//                'completed' => true,
//                'message'   => Mage::helper('prolabels')->__(
//                    '%d product(s) reindexed', $obj->getProcessed()
//                )
//            )));
//        } catch (Exception $e){
//            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//        }
//    }
//
//    public function systemProcessRelations($timeStart)
//    {
//        $obj = Mage::getSingleton('adminhtml/session')->getData('prolabels_object');
//        $reindexData = $obj->getReindexData();
//
//        $labelModel = Mage::getResourceModel('prolabels/label');
//        $ruleModel = Mage::getModel('prolabels/label');
//        for ($i = $obj->getOrderOffset(); $i < count($reindexData); $i++){
//            $ruleModel->load($obj->getLabelId());
//            $labelModel->apllySystemRule($ruleModel, $reindexData[$i]);
//
//            $timeEnd = time();
//            $obj->setProcessed($obj->getProcessed() + 1);
//            $obj->setOrderOffset($i + 1);
//            $timeRes = $timeEnd - $timeStart;
//            if ($timeRes > $this->_processTime){
//                return array(
//                    'processed' => $i + 1,
//                    'completed' => false
//                );
//            }
//        }
//        $obj->setQueryStep($obj->getQueryStep() + 1);
//        $obj->setReindexData(array());
//        return array(
//            'processed' => $i + 1,
//            'completed' => true
//        );
//    }
//
//    public function applyUserRulesAction()
//    {
//        $errorMessage = Mage::helper('prolabels')->__('Unable to apply rules.');
//        try {
//            Mage::getModel('prolabels/label')->applyAll();
//            $this->_getSession()->addSuccess(Mage::helper('prolabels')->__('The labels have been applied.'));
//        } catch (Mage_Core_Exception $e) {
//            $this->_getSession()->addError($errorMessage . ' ' . $e->getMessage());
//        } catch (Exception $e) {
//            $this->_getSession()->addError($errorMessage);
//        }
//        $this->_redirect('*/*');
//    }

    public function relatedGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('segmentationsuite/segments');
        $model->load($id);
        Mage::register('segmentationsuite_segments', $model);
        $this->loadLayout();

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tab_customers')->toHtml()
        );
    }
}
