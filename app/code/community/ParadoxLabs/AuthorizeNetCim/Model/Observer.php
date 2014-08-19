<?php

class ParadoxLabs_AuthorizeNetCim_Model_Observer extends Mage_Catalog_Model_Observer
{
	/**
	 * If we're doing a partial refund, don't mark it as fully refunded
	 * unless the full amount is done.
	 */
	public function processRefund( $observer ) {
		$memo = $observer->getEvent()->getCreditmemo();
		
		if( $memo->getOrder()->getPayment()->getMethod() == 'authnetcim' ) {
			if( $memo->getInvoice() && $memo->getInvoice()->getBaseTotalRefunded() < $memo->getInvoice()->getBaseGrandTotal() ) {
				$memo->getInvoice()->setIsUsedForRefund(false);
			}
		}
	}
}
