<?php

/**
 * Catalog Product api V2
 *
 * @author     Mihail
 */
class Fiuze_Importproducts_Model_Catalog_Product_Api_V2 extends Mage_Catalog_Model_Product_Api
{

    /**
     * Attach or Unattach some associated products to configurable product
     *
     * @param string skus of associated products 
     * @return boolean
     */
     public function assign($_sessionId, $_operation, $_configurableProductId, $_simpleProductIds)
     {
         $configurableProduct = Mage::getModel('catalog/product')->getCollection()
                            ->load($_configurableProductId);
         $associatedSimpleProducts = $product->getTypeInstance()->getUsedProductIds();
         
         if($_operation == 'add') {

            $associatedProductIds = array_merge($associatedSimpleProducts, $_simpleProductIds);                                

             Mage::getResourceSingleton('catalog/product_type_configurable')
                ->saveProducts($_configurableProductId, $associatedProductIds);

         } else if($_operation == 'remove'){
            $updatedProductIds = (array)null;
            
            $sp = 0;
            
            foreach($associatedSimpleProducts as $simpleId) {
                
                $isExisted = false;
                
                foreach($_simpleProductIds as $removedId) {
                    
                    if($simpleId == $removedId) {
                        $isExisted = true;
                    }
                }
                
                if(!$isExisted) {
                    
                    $updatedProductIds[$sp] = $simpleId;
                    
                    $sp++;
                }             
                
            }                         

             Mage::getResourceSingleton('catalog/product_type_configurable')
                ->saveProducts($_configurableProductId, $updatedProductIds);
         }         
            
         return true;
     }
}
