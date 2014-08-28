<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product link api V2
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Fiuze_ImportProducts_Model_Product_Link_Api_V2 extends Mage_Catalog_Model_Product_Link_Api_V2
{
    /**
     * Attach or Unattach some associated products to configurable product
     *
     * @param string skus of associated products 
     * @return boolean
     */
     public function configurableAssign($_operation, $_configurableProductId, $_simpleProductIds = array())
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
                
                 return Mage::helper('fiuze_importproducts')->__('Assigned the associated link to the configurable product with id ' . $_configurableProductId );   

             } else if($_operation == 'remove') {
                 
                 return Mage::helper('fiuze_importproducts')->__('Removed the associated link to the configurable product with id ' . $_configurableProductId );   
             }       
                
         } else {

            return Mage::helper('fiuze_importproducts')->__('Can\'t find this product which is configurable with id ' . $_configurableProductId );     

         }
     }
}