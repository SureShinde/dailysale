<?php
/**
 *
 * @category   Directshop
 * @package    Directshop_FraudDetection
 * @author     Ben James
 * @copyright  Copyright (c) 2008-2010 Directshop Pty Ltd. (http://directshop.com.au)
 */

/**
 * Fraud detection tab
 * Uses getGiftMessageHtml to insert itself as this is the only top level function available
 *
 * @category   Directshop
 * @package    Directshop_FraudDetection
 * @author     Ben James
 */
class Directshop_FraudDetection_Block_Adminhtml_Sales_Order_View_Tab_Info
    extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    
	// for 1.6.1 getGiftOptionsHtml
	// for 1.4.1 getGiftMessageHtml
	
	public function getGiftMessageHtml()
    {
		
		
		$parentHTML = parent::getGiftMessageHtml();

		
		$order = $this->getOrder();
		$payment = $order->getPayment();
		$output = "";
		$outputlines = array();
		$billingCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $order->getBillingAddress()->getCountry());
		$remainingCredits = Mage::getResourceModel('frauddetection/stats')->getValue("remaining_maxmind_credits");
		
		$result = Mage::getModel('frauddetection/result')->loadByOrderId($order->getId());
	
		
		
		$res = @unserialize(utf8_decode($result->getFraudData()));
		
		if (empty($res))
		{
			if ($payment->getId())
			{
				$res = $this->helper('frauddetection')->normaliseMaxMindResponse($this->helper('frauddetection')->getMaxMindResponse($payment));
				if (empty($res['err']) || !in_array($res['err'], Directshop_FraudDetection_Model_Result::$fatalErrors))
				{
					Mage::helper('frauddetection')->saveFraudData($res, $order);
				}
			}
			else
			{
				$res = array(
					'errmsg' => 'This order has no payment data.'
				);
			}
		}

		// for backwards compatibility
   		if (!isset($res['ourscore']) && isset($res['score']))
		{
			$res['ourscore'] = $res['score'];
		}

		
		if (isset($res['err']) && (in_array($res['err'], Directshop_FraudDetection_Model_Result::$fatalErrors) || $res['ourscore'] == -1))
		{
			$output = isset($res['errmsg']) ? $res['errmsg'] : $res['err'];
		}
		else
		{
			$score = 0;
			if (isset($res['ourscore']))
			{
				$score = $res['ourscore'];
			}
			
			$colour = "auto";
			if ($res['ourscore'] >= Mage::getStoreConfig('frauddetection/general/threshold'))
			{
				$colour = "red";
			}
			$output = "<table class='form-list' cellspacing='0'><tbody><tr><td class='label'><label>Fraud Score</label></td><td><span style='color:$colour;font-weight:bold;'>$score / 100</span></td></tr></tbody></table>";
			
			
			if (isset($res['explanation']))
			{
				$outputlines[] = $res['explanation'];
			}
			
			// COUNTRY
			$fullCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $res['countryCode']);
			$fullCountry = empty($fullCountry) ? $res['countryCode'] : $fullCountry;
			if (isset($res['countryMatch']) && $res['countryMatch'] == 'No')
			{
				$outputlines[] = array("Customer stated billing country as <strong>$billingCountry</strong> but IP address is located in <strong>$fullCountry</strong>.", 25);	
			}
			if (isset($res['highRiskCountry']) && $res['highRiskCountry'] == 'Yes')
			{
				$outputlines[] = array("$fullCountry is a high risk country.", 50);
			}
			if (isset($res['distance']))
			{
				$outputlines[] = array("Estimated distance between billing location and IP address is " . number_format($res['distance']) . "km.", round(round(100*(min(intval($res['distance']), 5000) / 20037), 1)));
			}
			if (isset($res['ip_isp']))
			{
				$outputlines[] = "Customer's ISP is " . $res['ip_isp'] . ".";
			}
			
			// PROXY
			if (isset($res['anonymousProxy']) && $res['anonymousProxy'] == 'Yes')
			{
				$outputlines[] = array("Anonymous proxy was used.", 50);
			}
			if (isset($res['proxyScore']) && $res['proxyScore'] > 0)
			{
				$outputlines[] = array(($res['proxyScore'] * 10) . "% possibility that an open proxy was used.", intval($res['proxyScore']) * 25);
			}
			
			// EMAIL
			if (isset($res['freeMail']) && $res['freeMail'] == 'Yes')
			{
				$outputlines[] = array("Free email address was used.", 25);
			}
			if (isset($res['carderEmail']) && $res['carderEmail'] == 'Yes')
			{
				$outputlines[] = array("Email was found in high risk database.", 50);
			}
			if (isset($res['highRiskUsername']) && $res['highRiskUsername'] == 'Yes')
			{
				$outputlines[] = array("High risk username was used.", 50);
			}
			if (isset($res['highRiskPassword']) && $res['highRiskPassword'] == 'Yes')
			{
				$outputlines[] = array("High risk password was used.", 50);
			}
			
			// BANK
			if (isset($res['binMatch']) && $res['binMatch'] == 'No')
			{
				$outputlines[] = array("Country of bank does not match billing country.", 20);
			}
			if (isset($res['binName']) && !empty($res['binName']))
			{
				$outputlines[] = "Bank used by customer was " . $res['binName'] . ".";
			}
			if (isset($res['binCountry']) && !empty($res['binCountry']))
			{
				$bankCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $res['binCountry']);
				$outputlines[] = "Country of bank is " . $bankCountry . ".";
			}
			
			// ADDRESS
			if (isset($res['custPhoneInBillingLoc']) && $res['custPhoneInBillingLoc'] == 'No')
			{
				$outputlines[] = "Phone number does not match billing location.";
			}
			if (isset($res['shipForward']) && $res['shipForward'] == 'Yes')
			{
				$outputlines[] = array("Shipping location is a known mail forwarding service.", 50);
			}
			if (isset($res['cityPostalMatch']) && $res['cityPostalMatch'] == 'No')
			{
				$outputlines[] = "Billing postcode does not match city.";
			}
			if (isset($res['shipCityPostalMatch']) && $res['shipCityPostalMatch'] == 'No')
			{
				$outputlines[] = "Shipping postcode does not match city.";
			}
		}
		
		array_multisort($outputlines, SORT_DESC);
		
		if (!empty($outputlines))
		{
			$output .= "<ul style='list-style-type:disc;margin-left:20px;'>";
			foreach ($outputlines as $line)
			{
				$desc = "";
				$score = "";
				if (is_array($line))
				{
					
					$desc = $line[0];
					if ((!isset($res['riskScore']) || empty($res['riskScore'])))
					{
						$score = $line[1] > 0 ? "+".$line[1] : "";
					}
				}
				else
				{
					$desc = $line;
				}
				$output .= "<li><span style=\"float:right; font-weight:bold\">$score</span>$desc</li>";
			}
			$output .= "</ul>";
		}
			
		$newHTML = <<<EOD
<div class="box-left">
	<div class="entry-edit-head"><h4 class="icon-head head-payment-method">Fraud Detection</h4></div>
	<fieldset>
		$output
		<small style="float:right;text-align:right;">Maxmind Credits Remaining: $remainingCredits<br/><a href="http://www.maxmind.com/app/ccfd_promo?promo=DIRECTSHOP3942">Purchase Additional Credits</a></small>
	</fieldset>
</div>
EOD;
				
		return $newHTML . $parentHTML;
    }
	
	
	
	
	
public function getGiftOptionsHtml()
    {
		
		
		$parentHTML = parent::getGiftOptionsHtml();

		
		$order = $this->getOrder();
		$payment = $order->getPayment();
		$output = "";
		$outputlines = array();
		$billingCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $order->getBillingAddress()->getCountry());
		$remainingCredits = Mage::getResourceModel('frauddetection/stats')->getValue("remaining_maxmind_credits");
		
		$result = Mage::getModel('frauddetection/result')->loadByOrderId($order->getId());
	
		
		
		$res = @unserialize(utf8_decode($result->getFraudData()));
		
		if (empty($res))
		{
			if ($payment->getId())
			{
				$res = $this->helper('frauddetection')->normaliseMaxMindResponse($this->helper('frauddetection')->getMaxMindResponse($payment));
				if (empty($res['err']) || !in_array($res['err'], Directshop_FraudDetection_Model_Result::$fatalErrors))
				{
					Mage::helper('frauddetection')->saveFraudData($res, $order);
				}
			}
			else
			{
				$res = array(
					'errmsg' => 'This order has no payment data.'
				);
			}
		}

		// for backwards compatibility
   		if (!isset($res['ourscore']) && isset($res['score']))
		{
			$res['ourscore'] = $res['score'];
		}

		
		if (isset($res['err']) && (in_array($res['err'], Directshop_FraudDetection_Model_Result::$fatalErrors) || $res['ourscore'] == -1))
		{
			$output = isset($res['errmsg']) ? $res['errmsg'] : $res['err'];
		}
		else
		{
			$score = 0;
			if (isset($res['ourscore']))
			{
				$score = $res['ourscore'];
			}
			
			$colour = "auto";
			if ($res['ourscore'] >= Mage::getStoreConfig('frauddetection/general/threshold'))
			{
				$colour = "red";
			}
			$output = "<table class='form-list' cellspacing='0'><tbody><tr><td class='label'><label>Fraud Score</label></td><td><span style='color:$colour;font-weight:bold;'>$score / 100</span></td></tr></tbody></table>";
			
			
			if (isset($res['explanation']))
			{
				$outputlines[] = $res['explanation'];
			}
			
			// COUNTRY
			$fullCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $res['countryCode']);
			$fullCountry = empty($fullCountry) ? $res['countryCode'] : $fullCountry;
			if (isset($res['countryMatch']) && $res['countryMatch'] == 'No')
			{
				$outputlines[] = array("Customer stated billing country as <strong>$billingCountry</strong> but IP address is located in <strong>$fullCountry</strong>.", 25);	
			}
			if (isset($res['highRiskCountry']) && $res['highRiskCountry'] == 'Yes')
			{
				$outputlines[] = array("$fullCountry is a high risk country.", 50);
			}
			if (isset($res['distance']))
			{
				$outputlines[] = array("Estimated distance between billing location and IP address is " . number_format($res['distance']) . "km.", round(round(100*(min(intval($res['distance']), 5000) / 20037), 1)));
			}
			if (isset($res['ip_isp']))
			{
				$outputlines[] = "Customer's ISP is " . $res['ip_isp'] . ".";
			}
			
			// PROXY
			if (isset($res['anonymousProxy']) && $res['anonymousProxy'] == 'Yes')
			{
				$outputlines[] = array("Anonymous proxy was used.", 50);
			}
			if (isset($res['proxyScore']) && $res['proxyScore'] > 0)
			{
				$outputlines[] = array(($res['proxyScore'] * 10) . "% possibility that an open proxy was used.", intval($res['proxyScore']) * 25);
			}
			
			// EMAIL
			if (isset($res['freeMail']) && $res['freeMail'] == 'Yes')
			{
				$outputlines[] = array("Free email address was used.", 25);
			}
			if (isset($res['carderEmail']) && $res['carderEmail'] == 'Yes')
			{
				$outputlines[] = array("Email was found in high risk database.", 50);
			}
			if (isset($res['highRiskUsername']) && $res['highRiskUsername'] == 'Yes')
			{
				$outputlines[] = array("High risk username was used.", 50);
			}
			if (isset($res['highRiskPassword']) && $res['highRiskPassword'] == 'Yes')
			{
				$outputlines[] = array("High risk password was used.", 50);
			}
			
			// BANK
			if (isset($res['binMatch']) && $res['binMatch'] == 'No')
			{
				$outputlines[] = array("Country of bank does not match billing country.", 20);
			}
			if (isset($res['binName']) && !empty($res['binName']))
			{
				$outputlines[] = "Bank used by customer was " . $res['binName'] . ".";
			}
			if (isset($res['binCountry']) && !empty($res['binCountry']))
			{
				$bankCountry = Zend_Locale_Data::getContent(Mage::app()->getLocale()->getLocale(), 'country', $res['binCountry']);
				$outputlines[] = "Country of bank is " . $bankCountry . ".";
			}
			
			// ADDRESS
			if (isset($res['custPhoneInBillingLoc']) && $res['custPhoneInBillingLoc'] == 'No')
			{
				$outputlines[] = "Phone number does not match billing location.";
			}
			if (isset($res['shipForward']) && $res['shipForward'] == 'Yes')
			{
				$outputlines[] = array("Shipping location is a known mail forwarding service.", 50);
			}
			if (isset($res['cityPostalMatch']) && $res['cityPostalMatch'] == 'No')
			{
				$outputlines[] = "Billing postcode does not match city.";
			}
			if (isset($res['shipCityPostalMatch']) && $res['shipCityPostalMatch'] == 'No')
			{
				$outputlines[] = "Shipping postcode does not match city.";
			}
		}
		
		array_multisort($outputlines, SORT_DESC);
		
		if (!empty($outputlines))
		{
			$output .= "<ul style='list-style-type:disc;margin-left:20px;'>";
			foreach ($outputlines as $line)
			{
				$desc = "";
				$score = "";
				if (is_array($line))
				{
					
					$desc = $line[0];
					if ((!isset($res['riskScore']) || empty($res['riskScore'])))
					{
						$score = $line[1] > 0 ? "+".$line[1] : "";
					}
				}
				else
				{
					$desc = $line;
				}
				$output .= "<li><span style=\"float:right; font-weight:bold\">$score</span>$desc</li>";
			}
			$output .= "</ul>";
		}
			
		$newHTML = <<<EOD
<div class="box-left">
	<div class="entry-edit-head"><h4 class="icon-head head-payment-method">Fraud Detection</h4></div>
	<fieldset>
		$output
		<small style="float:right;text-align:right;">Maxmind Credits Remaining: $remainingCredits<br/><a href="http://www.maxmind.com/app/ccfd_promo?promo=DIRECTSHOP3942">Purchase Additional Credits</a></small>
	</fieldset>
</div>
EOD;
				
		return $newHTML . $parentHTML;
    }
	
	
}