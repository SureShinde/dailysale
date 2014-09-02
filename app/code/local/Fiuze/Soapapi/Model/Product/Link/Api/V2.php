<?php

/**
 * Catalog product link api V2
 *
 * @category   Mage
 * @author     Mihail
 */
class Fiuze_Soapapi_Model_Product_Link_Api_V2 extends Mage_Catalog_Model_Product_Link_Api_V2
{
    /**
     * Attach or Unattach some associated products to configurable product
     *
     * @param string skus of associated products 
     * @return boolean
     */
     public function assigned($_operation, $_configurableProductId, $_simpleProductIds = array())
     {
         $configurableProduct = Mage::getModel('catalog/product')
         //                   ->getCollection()
                            ->load($_configurableProductId);
         //$associatedSimpleProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($_configurableProductId);
         if($configurableProduct->getTypeId() == 'configurable'){
            $associatedSimpleProducts = $configurableProduct->getTypeInstance()->getUsedProductIds();
            $associatedProductIds = array();
            $unlinkedProductIds = array();

             if($_operation == 'add') {
                 foreach($associatedSimpleProducts as $id) {
                    $associatedProductIds[$id] = 1;
                 }
                 foreach($_simpleProductIds as $id) {
                    $associatedProductIds[$id] = 1;
                 }

                 Mage::getResourceModel('catalog/product_type_configurable')
                        ->saveProducts($configurableProduct, array_keys($associatedProductIds));

             } else if($_operation == 'remove') {
                 
                 $resource = Mage::getSingleton('core/resource');
                 $read = $resource->getConnection('core_write');
                 $tableName = $resource->getTableName('catalog/product_super_link');
                 
                 
                 //$read->beginTransaction();
                 
                 foreach($_simpleProductIds as $id){

                     $condition = array(          
                      $read->quoteInto('parent_id=?', $_configurableProductId) . ' AND '
                        . $read->quoteInto('product_id=?', $id)
                     );
                     
                     $read->delete($tableName, $condition);
                 }
                 
                 $read->commit();
                 
             }
             
             if($_operation == 'add') {
                
                 return Mage::helper('fiuze_soapapi')->__('Assigned the associated link to the configurable product with id ' . $_configurableProductId );   

             } else if($_operation == 'remove') {
                 
                 return Mage::helper('fiuze_soapapi')->__('Removed the associated link to the configurable product with id ' . $_configurableProductId );   
             }       
                
         } else {

            return Mage::helper('fiuze_soapapi')->__('Can\'t find this product which is configurable with id ' . $_configurableProductId );     

         }
     }
}