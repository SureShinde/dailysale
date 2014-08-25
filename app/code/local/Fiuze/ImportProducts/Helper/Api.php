<?php
/**
 * Soap Api Helper
 *
 * @author Mihail
 */
class Fiuze_ImportProducts_Helper_Api extends Mage_Core_Helper_Abstract
{
    protected $_session_id = '';
    protected $_client = '';
    /**
     * Connect to soapclient
     *
     * @params $_soapclient, $_apiuser, $apikey
     * @return boolean
     */
    public function connect($_soapclient, $_apiuser, $_apikey)
    {
        $options = array(
            'trace' => true,
            'connection_timeout' => 120,
            'wsdl_cache' => WSDL_CACHE_NONE,
        );
        $_client = new SoapClient($_soapclient, $options);
        $_session_id = $_client->login($_apiuser, $_apikey);
        
        return true;        
    }
    
    /**
     * Get the session id
     *
     * @return string:sessionId
     */
     public function getSessionId()
     {
        return $_session_id;            
     }

    /**
     * Add link as an associated product to configurable product by sku
     *
     * @return string
     */
     public function addLinkBySku($_proxy, $_sessionId, $_configurableProductSku, $_associatedPorductSku)
     {
         return $_proxy->call($_sessionId, 'product_link.assign', array('configurable', $_configurableProductSku, $_associatedPorductSku));
     }

    /**
     * remove link of an associated product in configurable product by sku
     *
     * @return string
     */
     public function removeLinkBySku($_proxy, $_sessionId, $_configurableProductSku, $_associatedPorductSku)
     {
         return $_proxy->call($_sessionId, 'product_link.remove', array('configurable', $_configurableProductSku, $_associatedPorductSku));
     }

    /**
     * Add link as an associated product to configurable product by productId
     *
     * @return string
     */
     public function addLinkByProductId($_proxy, $_sessionId, $_configurableProductId, $_associatedPorductId)
     {
         $configurableProductInfo = $_proxy->call($_sessionId, 'product.info', $_configurableProductId);
         $associatedProductInfo = $_proxy->call($_sessionId, 'catalog_product.info', $_configurableProductId);
         
         $this->addLinkBySku($configurableProductInfo['sku'], $associatedProductInfo['sku']);
     }

    /**
     * remove link of an associated product in configurable product by productId
     *
     * @return string
     */
     public function removeLinkByProductId($_proxy, $_sessionId, $_configurableProductId, $_associatedPorductId)
     {
         $configurableProductInfo = $_proxy->call($_sessionId, 'catalog_product.info', $_configurableProductId);
         $associatedProductInfo = $_proxy->call($_sessionId, 'catalog_product.info', $_configurableProductId);
         
         $this->removeLinkBySku($configurableProductInfo['sku'], $associatedProductInfo['sku']);
     }

    /**
     * Add link as an associated product to configurable product forsoap_v2
     *
     * @return string
     */
     public function addLink_v2($_proxy, $_sessionId, $_configurableProductId, $_associatedProductId)
     {
         $configurableProductInfo = $_proxy->catalogProductInfo($_sessionId, $_configurableProductId);
         $associatedProductInfo = $_proxy->catalogProductInfo($_sessionId, $_associatedProductId);
         
         /*$attributeSets = $_proxxy->catalogProductAttributeSetList($_sessionId);
         $attributeSet = current($attributeSets);
         
         $result = $_proxy->catalogProductCreate($_sessionId, 'simple', $attributeSet->set_id, $associatedProductInfo->sku, (array)$associatedProductInfo);*/
         $result = $_proxy->catalogProductLinkAssign($_sessionId, 'grouped', $configurableProductInfo->sku, $associatedProductInfo->sku);
         return $result;
     }

    /**
     * remove link of an associated product in configurable product for soap_v2
     *
     * @return string
     */
     public function removeLink_v2($_proxy, $_sessionId, $_configurableProductId, $_associatedProductId)
     {
         $configurableProductInfo = $_proxy->catalogProductInfo($_sessionId, $_configurableProductId);
         $associatedProductInfo = $_proxy->catalogProductInfo($_sessionId, $_associatedProductId);

         $result = $_proxy->catalogProductLinkRemove($_sessionId, 'grouped', $configurableProductInfo->sku, $associatedProductInfo->sku);
         return $result;
     }
     
    /**
     * attach or remove link of an associated product in configurable product for soap_v2
     *
     * @return string
     */
     public function linkAssign_v2($_proxy, $_sessionId, $_operation, $_configurableProductId, $_associatedProductIds)
     {
         $result = $_proxy->catalogProductTypeConfigurableAssign($_sessionId, 'grouped', $_configurableProductId, $_associatedProductIds);
         return $result;
     }
     

}
