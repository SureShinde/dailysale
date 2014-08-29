<?php
/**
 * Importproducts controller
 *
 * @author Mihail
 */
class Fiuze_Importproducts_Adminhtml_ImportproductsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return Fiuze_Importproducts_Adminhtml_ImportproductsController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('fiuze/importproducts')
            ->_addBreadcrumb(
                  Mage::helper('fiuze_importproducts')->__('Fiuze'),
                  Mage::helper('fiuze_importproducts')->__('Fiuze')
              )
            ->_addBreadcrumb(
                  Mage::helper('fiuze_importproducts')->__('ImportProducts'),
                  Mage::helper('fiuze_importproducts')->__('ImportProducts')
              )
			//->_addContent($this->getLayout()->createBlock('fiuze_importproducts/adminhtml_importproducts_edit'))
        ;
		$this->_setActiveMenu('fiuze/importproducts');

		return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('Fiuze'))
             ->_title($this->__('ImportProducts'));

		$this->_initAction();
        $this->renderLayout();
    }


    /**
     * Import action
     */
    public function importAction()
    {
        $redirectPath   = '*/*';
        $redirectParams = array();

        // check if data sent
        $data = $this->getRequest()->getPost();
        if ($data) {
            //$data = $this->_filterPostData($data);
            try {
                if (isset($_FILES['importfile'][name]) && (file_exists($_FILES['importfile']['tmp_name']))) {
                    $hasError = false;
                    /* @var $fileHelper Fiuze_Importproducts_Helper_File */
                    $fileHelper = Mage::helper('fiuze_importproducts/file');

                    // upload new file
                    $importContent = $fileHelper->uploadFile('importfile');
                    
                    if($importContent) {
                        try{

                            $soapclient = Mage::helper('fiuze_importproducts/data')->getSoapClient();
                            $apiuser = Mage::helper('fiuze_importproducts/data')->getApiUser();
                            $apikey = Mage::helper('fiuze_importproducts/data')->getApiKey();
                            
                            $options = array(
                                'trace' => true,
                                'connection_timeout' => 120,
                                'wsdl_cache' => WSDL_CACHE_NONE,
                            );

                            $proxy = new SoapClient($soapclient, $options);
                            $sessionId = $proxy->login($apiuser, $apikey);
                           
                            $confSp = 0;
                                               
                            foreach($importContent->configProductId as $configurableProductId) {

                                $linkedIds = array();

                                $options = array();
                                $optionCount = $importContent->configOptions[$confSp]->id->count();
                                
                                for($i=0; $i < $optionCount; $i++ ) {
                                    $linkedIds[$i] = (string)$importContent->configOptions[$confSp]->id[$i];    
                                } 
                                $result = Mage::helper('fiuze_importproducts/api')->linkAssign_v2($proxy, $sessionId, (string)$importContent->operation[$confSp], (string)$configurableProductId, $linkedIds);                                            
                                //$result = Mage::helper('fiuze_importproducts/api')->configurableAssign($sessionId, (string)$importContent->operation[$confSp], (string)$configurableProductId, $linkedIds);                                            
                                $this->_getSession()->addNotice($result);                                    

                                $confSp++;             
                            } 
                            
                            
                        } catch (Exception $e) {
                            $hasError = true;
                            
                            $this->_getSession()->addError($e->getMessage());
                        }
                            
                    } else {
                        $hasError = true;
                        $this->_getSession()->addError(Mage::helper('fiuze_importproducts')->__('Loading products is failed'));
                    }
                } else {
                    $hasError = true;
                    $this->_getSession()->addError(Mage::helper('fiuze_importproducts')->__('File upload is failed'));
                }
            } catch (Mage_Core_Exception $e) {
                $hasError = true;
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $hasError = true;
                $this->_getSession()->addException($e,
                    Mage::helper('fiuze_importproducts')->__('An error occurred while importing the file.')
                );
            }

            if ($hasError) {
                $this->_getSession()->setFormData($data);
                $redirectPath   = '*/*/index';
                $redirectParams = array('id' => $this->getRequest()->getParam('id'));
            }
            else {
                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('fiuze_importproducts')->__('Successfully imported.')
                );
            }
        }

        $this->_redirect($redirectPath, $redirectParams);
    }

    /**
    *  Filtering posted data. Converting localized data if needed
    * 
    *  @param array
    *  @return array
    */
    protected function _filterPostData($data)
    {
        $data = $this->_fileterDates($data, array('time_published'));
        return $data;
    }

    /**
     * Flush News Posts Images Cache action
     */
    public function flushAction()
    {
        if (Mage::helper('magentostudy_news/image')->flushImagesCache()) {
            $this->_getSession()->addSuccess('Cache successfully flushed');
        } else {
            $this->_getSession()->addError('There was error during flushing cache');
        }
        $this->_forward('index');
    }
}