<?php

class TM_SegmentationSuite_Adminhtml_Segmentationsuite_SegmentsController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_customerCount = 25;

    protected $_processTime = 25;

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('templates_master/segmentationsuite/segments')
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Templates Master'),
                Mage::helper('segmentationsuite')->__('Templates Master')
            )
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Segmentation Suite'),
                Mage::helper('segmentationsuite')->__('Segmentation Suite')
            )
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Customer Segments'),
                Mage::helper('segmentationsuite')->__('Customer Segments')
            );
        return $this;
    }

    /**
     * Banner list page
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent(
            $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments')
        );
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
                    ->addError(
                        Mage::helper('segmentationsuite')->__('This segment no longer exists')
                    );
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
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Templates Master'),
                Mage::helper('segmentationsuite')->__('Templates Master'))
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Segmentation Suite'),
                Mage::helper('segmentationsuite')->__('Segmentation Suite'))
            ->_addBreadcrumb(
                Mage::helper('segmentationsuite')->__('Customer Segments'),
                Mage::helper('segmentationsuite')->__('Customer Segments'));

        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true);

        $this
            ->_addBreadcrumb(
                $id ? Mage::helper('segmentationsuite')->__('Edit Segment') : Mage::helper('segmentationsuite')->__('New Segment'), $id ? Mage::helper('segmentationsuite')->__('Edit Segment') : Mage::helper('segmentationsuite')->__('New Segment')
            )
            ->_addContent(
                $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit')
                    ->setData('action', $this->getUrl('*/*/save'))
                    ->setData('form_action_url', $this->getUrl('*/*/save'))
            )
            ->_addLeft(
                $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tabs')
            )
            ->renderLayout();
    }

    /**
     * Segments grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock(
                'segmentationsuite/adminhtml_segments_grid'
            )->toHtml()
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
                    'segmentationsuite/adminhtml_widget_chooser_product',
                    'segmentationsuite_widget_chooser_product',
                    array('js_form_object' => $this->getRequest()->getParam('form')));
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

   public function indexSegmentAction()
   {
       try {
           $timeStart = time();
           $segmentModel = Mage::getResourceModel('segmentationsuite/segments');
           $segment = Mage::getModel('segmentationsuite/segments');

           if ($this->getRequest()->getParam('clear_session')) {
               $obj = new Varien_Object();
               Mage::getSingleton('adminhtml/session')->setData('segments_object', $obj);
               $segmentModel->deleteSegmentIndex($this->getRequest()->getParam('segment_id'));
               $collection = Mage::getModel('customer/customer')->getCollection();
               $storeIds = $this->getRequest()->getParam('stores');
               if (!in_array(0, $storeIds)) {
                   $collection->addFieldToFilter('store_id', array('in' => $storeIds));
               }

               $obj->setQueryStep(0);
               $obj->setProcessed(0);
               $obj->setCustomers($collection->getSize());
               $obj->setCustomerCount($this->_customerCount);
           } else {
               $obj = Mage::getSingleton('adminhtml/session')->getData('segments_object');
               $queryOffset = $obj->getQueryStep();
           }
           if ($obj->getProcessed() < $obj->getCustomers()) {
               $segment->indexSegment($obj->getCustomerCount(), $obj->getQueryStep(), $this->getRequest()->getParam('segment_id'));
               $obj->setQueryStep($obj->getQueryStep() + 1);
               $obj->setProcessed($obj->getProcessed() + $obj->getCustomerCount());
               if ($obj->getProcessed() >= $obj->getCustomers()) {
                   $indexing = $obj->getCustomers();
               } else {
                   $indexing = $obj->getProcessed();
               }
               return $this->getResponse()->setBody(
                   Mage::helper('core')->jsonEncode(array(
                       'completed' => false,
                       'message'   => Mage::helper('segmentationsuite')->__(
                           '%d customer(s) reindexed', $indexing
                       )
               )));
           } else {
               Mage::getSingleton('adminhtml/session')->unsetData('segments_object');
               Mage::getSingleton('adminhtml/session')->addSuccess(
                   Mage::helper('segmentationsuite')->__(
                       '%d customer(s) was successfully reindexed', $obj->getCustomers()
                    )
               );
               return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                   'processed' => $obj->getCustomers(),
                   'completed' => true,
                   'message'   => Mage::helper('segmentationsuite')->__(
                       '%d customer(s) reindexed', $obj->getCustomers()
                   )
               )));
           }
       } catch (Exception $e) {
           Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
       }
   }

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
