<?php
/**
 * Fiuze Setup Block
 *
 * author@ Mihail
 */
class Fiuze_Setup_Block_Products extends Mage_Core_Block_Abstract implements Mage_Widget_Block_Interface
{

    /**
     * Produces html for category products
     *
     * @return string
     */
    protected function _toHtml()
    {
		//gets Day of today
		$day = date("j", Mage::getModel('core/date')->timestamp(time()));
		
		$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $day);
		$cat_det = $category->getData();
		$categoryId = $cat_det[0]["entity_id"];

		$categoryIds = array($categoryId);

		$productCollection = Mage::getModel('catalog/product')
						 ->getCollection()
						 ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
						 ->addAttributeToSelect('*')
						 ->addAttributeToFilter('category_id', array('in' => $categoryIds));        

		$_collectionSize = $productCollection->count();

		$i = 0;
		$_columnCount = 4;
		
		$html = '<div style="clear:both"></div><div class="newsletter-products" style="font-size:10px;text-align:center;">';
		
		foreach( $productCollection as $product ){
			if( $i++ % $_columnCount == 0 ){ 
				$html .= '<ul class="products-grid" style="list-style:none;">';
			}

			if( ($i-1) % $_columnCount == 0 ){
				$className = "first";
			}else if( $i % $_columnCount == 0 ){
				$className = "last";
			}
			
			$productImageWidth = 100;
			$productImageHeight = 100;

			$productUrl = $product->getProductUrl();
			$productName = $product->getName();
			$productImageSrc = Mage::helper('catalog/image')->init($product, 'small_image')->resize($productImageWidth, $productImageHeight);
			//$productImageSrc = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
			$productPrice = Mage::helper('core')->currency($product->getPrice(),true,false);

			$html .= ('<li class="item ' . $className . '" style="width:150px;float:left;">' .
						'<a href="' . $productUrl . '" title="' . $productName . '">' .
							'<img src="' . $productImageSrc. '" alt="' . $productName . '" border="0" />' .
						'</a>' .
						'<h2 class="product-name">' . 
							'<a href="' . $productUrl . '" title="' . $productName . '">' . $productName . '</a>' . 
						'</h2>' .
						'<div class="price-box">' .
							$productPrice .
						'</div>');
						/*.
						'<div class="actions">');
						
						if( $product->isSaleable() ){
							$cartUrl = Mage::getUrl('checkout/cart/add/') . "product/" . $product->getId() . "/";
			$html .=		('<button class="button" onclick="setLocation(\'' . $cartUrl . '/\')" title="Add To Cart" type="submit">' .
								'<span><span>Add to Cart</span></span></button>');
						}else{
			$html .=		'<p class="availability out-of-stock"><span>Out of stock</span></p>';
						}

			$html .=	('</div>' .
					'</li>');*/
			
			if( $i % $_columnCount == 0 || $i == $_collectionSize ){
				
				$html .= '</ul>';
			
			}
        }
		
		$html .= '</div><div style="clear:both"></div>';
		
		//Mage::log($html, null, "fiuzesetup.log");

        return $html;
    }

} 