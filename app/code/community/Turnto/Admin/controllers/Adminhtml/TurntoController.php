<?php

class Turnto_Admin_Adminhtml_TurntoController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		
		$resource = Mage::getSingleton('core/resource');

		Mage::register('websites', Mage::app()->getWebsites());
		
		$this->_addLeft($this->getLayout()->createBlock('Turnto_Admin_Block_ShowTabsAdminBlock'));
		
		$this->renderLayout();
	}
	
	public function redirectAction()
	{
		$this->_redirectUrl('http://www.turnto.com');
	}
	
	public function ratingsAction()
    {
 
         Mage::helper('adminhelper1')->loadRatings();
         $message = $this->__('Products Ratings successfully updated.');
         Mage::getSingleton('adminhtml/session')->addSuccess($message);
         $this->_redirect('*/*/', array('active_tab' => 'turnto_ratings_feed_tab'));
  	  
    }

	public function postAction()
    {
        $post = $this->getRequest()->getPost();
        $catalogFeed = true;
        
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            
            $path = Mage::getBaseDir('media') . DS . 'turnto/';
            mkdir($path, 0755);
            
            if($post['feed_type'] == 'historical'){            
				/* form processing */        
				$startDate = $post['start_date'];
				
				if($startDate == null || $startDate == ""){
					Mage::getSingleton('adminhtml/session')->addError("Start Date is required");
					$this->_redirect('*/*/', array('active_tab' => 'turnto_hist_feed_tab'));
					return;
				}

				$scope = $post['scope'];
				$handle = fopen($path . 'histfeed.csv', 'w');

				if (!$handle) {
					Mage::throwException($this->__('Could not create historical feed file in directory ' . $path));
				}
				
				fwrite($handle, "ORDERID\tORDERDATE\tEMAIL\tITEMTITLE\tITEMURL\tITEMLINEID\tZIP\tFIRSTNAME\tLASTNAME\tSKU\tPRICE\tITEMIMAGEURL");
				fwrite($handle, "\n");
			
				$fromDate = date('Y-m-d H:i:s', strtotime($startDate));
				Mage::app();	
				$orders = Mage::getModel('sales/order')
				->getCollection()
				->addFieldToFilter('store_id', $scope)
				->addAttributeToFilter('created_at', array('from'=>$fromDate))
				->addAttributeToSort('entity_id', 'DESC')
				->setPageSize(100);

				$pages = $orders->getLastPageNumber();

				for ($curPage = 1; $curPage <= $pages; $curPage++) {
					$orders->setCurPage($curPage);
					$orders->load();
					foreach ($orders as $order) {
						$itemlineid = 0;
						foreach ($order->getAllVisibleItems() as $item) {
							//ORDERID
							fwrite($handle, $order->getRealOrderId());
							fwrite($handle, "\t");
							//ORDERDATE
							fwrite($handle, $order->getCreatedAtDate()->toString('Y-MM-d'));
							fwrite($handle, "\t");
							//EMAIL
							fwrite($handle, $order->getCustomerEmail());
							fwrite($handle, "\t");
							//ITEMTITLE
							fwrite($handle, $item->getName());
							fwrite($handle, "\t");
							//ITEMURL
							$product = $item->getProduct();
							fwrite($handle, $product->getProductUrl());
							fwrite($handle, "\t");
							//ITEMLINEID
							fwrite($handle, $itemlineid++);
							fwrite($handle, "\t");
							//ZIP
							fwrite($handle, $order->getShippingAddress()->getPostcode());
							fwrite($handle, "\t");
							//FIRSTNAME
							$name = explode(' ', $order->getCustomerName());
							fwrite($handle, $name[0]);
							fwrite($handle, "\t");
							//LASTNAME
							if (isset($name[1])) {
								fwrite($handle, $name[1]);
							}
							fwrite($handle, "\t");
							//SKU
							fwrite($handle, $item->getSku());
							fwrite($handle, "\t");
							//PRICE
							fwrite($handle, $item->getOriginalPrice());
							fwrite($handle, "\t");
							//ITEMIMAGEURL
							if ($product->getImage() != null && $product->getImage() != "no_selection") {
								fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage()));
							} else if ($product->getSmallImage() != null && $product->getSmallImage() != "no_selection") {
								fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getSmallImage()));
							} else if ($product->getThumbnail() != null && $product->getThumbnail() != "no_selection") {
								fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getThumbnail()));
							}
							fwrite($handle, "\n");
						}
					}
					$orders->clear();
				}
				
				fclose($handle); 
							
				$message = $this->__('The historical feed was successfully generated. Click the &quot;Download historical feed&quot; link to download.');
				$catalogFeed = false;
            }
            else{
            	/* form processing */        
				$websiteId = $post['websiteId'];
				$storeId = 1;				
				if (isset($websiteId)) {
					$split = explode('_', $websiteId);
					$websiteId = $split[0];
					$storeId = $split[1];
				} else {
					$websiteId = 1;
				}

				$baseUrl =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                                $baseMediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product';
                                if(!isset($storeId)){
					$storeId = 1;
				}
                                $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                                $baseMediaUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product';

				$handle = fopen($path . 'catfeed.csv', 'w');
				
				fwrite($handle, "SKU\tIMAGEURL\tTITLE\tPRICE\tCURRENCY\tACTIVE\tITEMURL\tCATEGORY\tKEYWORDS\tREPLACEMENTSKU\tINSTOCK\tVIRTUALPARENTCODE\tCATEGORYPATHJSON\tISCATEGORY");
				fwrite($handle, "\n");
			
                		$products = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection()->addAttributeToSelect('name')->addAttributeToSelect('*')->addAttributeToSelect('price')->addAttributeToSelect('image')->addWebsiteFilter($websiteId);
				if ($products) {
                        		foreach ($products as $product) {
                                		$product->setStoreId($storeId);
                                		fwrite($handle, $product->getSku());
                                		fwrite($handle, "\t");
 						if($product->getImage() != null && $product->getImage() != "no_selection") {
                                 		       fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl( $product->getImage() ));
                               			} else if ($product->getSmallImage() != null && $product->getSmallImage() != "no_selection") {
                                        		fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl( $product->getSmallImage() ));
                                		} else if ($product->getThumbnail() != null && $product->getThumbnail() != "no_selection") {
                                        		fwrite($handle, Mage::getModel('catalog/product_media_config')->getMediaUrl( $product->getThumbnail() ));
                                		}                               		
						fwrite($handle, "\t");
                                		fwrite($handle, $product->getName());
                                		fwrite($handle, "\t");
                                		fwrite($handle, $product->getPrice());
                                		fwrite($handle, "\t");
                                		//CURRENCY
                                		fwrite($handle, "\t");
                                		//ACTIVE
                                		fwrite($handle, "Y");
                                		fwrite($handle, "\t");
                                		//ITEMURL
                                		fwrite($handle, $product->getProductUrl());
                                		fwrite($handle, "\t");
                                		//CATEGORY
						$ids = $product->getCategoryIds();
                                                fwrite($handle, (isset($ids[0]) ? $ids[0] : ''));
                                		fwrite($handle, "\t");
                                		// KEYWORDS
                                		fwrite($handle, "\t");
                                		// REPLACEMENTSKU
                                		fwrite($handle, "\t");
                                		//VIRTUALPARENTCODE
                                		fwrite($handle, "\t");
                                		//CATEGORYPATHJSON
                                		fwrite($handle, "\t");
                                		//ISCATEGORY
                                		fwrite($handle, "n");
                                		fwrite($handle, "\n");
                        		}
                		}	
				
				$categories = Mage::getModel('catalog/category')->setStoreId($storeId)->getCollection()->addAttributeToSelect('name');
                		if ($categories) {
                        		foreach ($categories as $category) {
                                		if ($category->getId() == 1) {
							continue;
						}
						$category->setStoreId($storeId);
                                		fwrite($handle, $category->getId());
                                		fwrite($handle, "\t");
						//IMAGEURL
                                		fwrite($handle, "\t");
                                		//TITLE
                                		fwrite($handle, $category->getName());
                                		fwrite($handle, "\t");
                                		//PRICE
                                		fwrite($handle, "\t");
                                		//CURRENCY
                                		fwrite($handle, "\t");
                                		//ACTIVE
                                		fwrite($handle, "Y");
                                		fwrite($handle, "\t");
                                		//ITEMURL
                                		fwrite($handle, $category->getUrl());
                                		fwrite($handle, "\t");
                                		//CATEGORY
                                		fwrite($handle, $category->getParentCategory()->getId());
                                		fwrite($handle, "\t");
                                		//KEYWORDS
                                		fwrite($handle, "\t");
                                		//REPLACEMENTSKU
                                		fwrite($handle, "\t");
                                		//VIRTUALPARENTCODE
                                		fwrite($handle, "\t");
                                		//CATEGORYPATHJSON
                                		fwrite($handle, "\t");
                                		//ISCATEGORY
                                		fwrite($handle, "Y");
                                		fwrite($handle, "\n");

                        		}
                		}

				fclose($handle); 
							
				$message = $this->__('The catalog feed was successfully generated. Click the &quot;Download catalog feed&quot; link to download.');
            }
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        
        if($catalogFeed){
			$this->_redirect('*/*/', array('active_tab' => 'turnto_catalog_feed_tab'));
		}else{
			$this->_redirectUrl(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'media/turnto/histfeed.csv');
		}
    }
}
