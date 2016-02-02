<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_CheckoutController extends Mage_Core_Controller_Front_Action
{
    protected $_quote;

    protected $_addressFieldMapping = array(
        'postalCode'        => 'postcode',
        'locality'          => 'city',
        'countryCodeAlpha2' => 'country_id',
        'state'             => 'region',
        'countryCode'       => 'country_id',
        'phone'             => 'telephone',
    );

    protected $_addressSpecificFields = array(
        'recipientName',
        'line1',
        'line2',
        'streetAddress',
        'extendedAddress',
    );
    
    /**
     * Here customer is redirected after checkout on PayPal side to finish checkout
     */
    public function reviewAction()
    {
        try {
            if (Mage::app()->getRequest()->getParam('checkoutflow') == true) {
                $allDataSet = true;
            } else {
                $allDataSet = $this->_initCheckout();
            }
            if ($allDataSet) {
                $quote = $this->_getQuote();
                $this->loadLayout();
                $this->_initLayoutMessages('checkout/session');
                $reviewBlock = $this->getLayout()->getBlock('braintree.paypal.review');
                $reviewBlock->setQuote($quote);
                $reviewBlock->getChild('details')->setQuote($quote);
                if ($reviewBlock->getChild('shipping_method')) {
                    $reviewBlock->getChild('shipping_method')->setQuote($quote);
                }
                $this->renderLayout();
            } else {
                $this->_redirect('*/*/editaddress', array('action' => 'add'));
            }
            return;
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = Mage::getSingleton('checkout/session')->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Returns checkout model instance, native onepage checkout is used
     * 
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Initializes checkout for further use
     * 
     * @return boolean
     */
    protected function _initCheckout()
    {
        $shippingAddressExist = true;
        $quote = $this->_getQuote();
        $checkout = $this->_getCheckout();
        $checkout->initCheckout();
        
        // Adresses
        $quote->removeAllAddresses();

        // Billing address data
        $data = $this->_prepareAddress(Mage_Customer_Model_Address_Abstract::TYPE_BILLING);
        $result = $checkout->saveBilling($data, 0);
        $this->_processCheckoutError($result, "billing address");
        // Check if billing country is allowed
        $paypalModel = Mage::getModel('braintree_payments/paypal');
        if (!$paypalModel->canUseForCountry($quote->getBillingAddress()->getCountryId())) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('This payment method is not allowed for your country.')
            );
        }
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());

        // Shipping address data
        $data = $this->_prepareAddress(Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING);
        if ($data) {
            $data['telephone'] = $quote->getBillingAddress()->getTelephone();
            $result = $checkout->saveShipping($data, 0);
            $this->_processCheckoutError($result, "shipping address");
        } else {
            $shippingAddressExist = false;
        }

        // Payment data
        $nonce = Mage::app()->getRequest()->getParam('payment_method_nonce');
        if (!$nonce) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('There was an error processing the payment.')
            );
        }
        $data = array('method' => Braintree_Payments_Model_Paypal::PAYMENT_METHOD_CODE, 'nonce' => $nonce);
        $result = $checkout->savePayment($data);
        $this->_processCheckoutError($result, "payment");

        // Collecting totals in the end
        $quote->setTotalsCollectedFlag(false)
            ->collectTotals()
            ->save();
        return $shippingAddressExist;
    }

    /**
     * Converts address from PayPal into Magento supported array
     * 
     * @param string $type
     * @return array
     */
    protected function _prepareAddress($type)
    {
        $data = array('street' => array());
        $post = Mage::app()->getRequest()->getPost($type);
        if (!$post) {
            return array();
        }
        foreach ($post as $key => $value) {
            if (in_array($key, $this->_addressSpecificFields)) {
                if ($key == 'recipientName') {
                    $names = explode(' ', $value);
                    $data['firstname'] = $names[0];
                    $data['lastname'] = $names[1];
                } else if ($key == 'line1' || $key == 'streetAddress') {
                    $data['street'][0] = $value;
                } else if ($key == 'line2' || $key == 'extendedAddress') {
                    $data['street'][1] = $value;
                }
            } else if (array_key_exists($key, $this->_addressFieldMapping)) {
                $data[$this->_addressFieldMapping[$key]] = $value;
            } else {
                $data[strtolower($key)] = $value;
            }
        }
        return $data;
    }

    /**
     * Check if there was error during processing checkout data
     * 
     * @param array $data
     */
    protected function _processCheckoutError($data, $type)
    {
        if ($data && isset($data['error'])) {
            $additionalMessage = '';
            if (isset($data['message']) && $data['message']) {
                if (is_array($data['message'])) {
                    $additionalMessage = implode('. ', $data['message']);
                } else {
                    $additionalMessage = $data['message'];
                }
            }
            $message = Mage::helper('braintree_payments')
                ->__("There was an error processing the $type.");
            if ($additionalMessage) {
                $message .= ' ' . $additionalMessage;
            }
            Mage::throwException($message);
        }
    }

    /**
     * Update shipping method
     */
    public function shippingMethodAction()
    {
        try {
            $result = $this->_getCheckout()->saveShippingMethod($this->getRequest()->getParam('shipping_method'));
            $this->_processCheckoutError($result, "shipping method");
            $this->_getQuote()->collectTotals()->save();
            $this->loadLayout('braintree_checkout_review_details');
            $this->getResponse()->setBody(
                $this->getLayout()
                ->getBlock('root')
                ->setQuote($this->_getQuote())
                ->toHtml()
            );
        } catch (Exception $e) {
            $this->getResponse()->setBody('<script type="text/javascript">alert("' . $e->getMessage() . '");</script>');
        }
    }

    /**
     * Place Order
     */
    public function placeOrderAction()
    {
        try {
            $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
            if ($requiredAgreements) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                if (array_diff($requiredAgreements, $postedAgreements)) {
                    Mage::throwException(
                        $this->__('Please agree to all the terms and conditions before placing the order.')
                    );
                }
            }
            $this->_getQuote()->collectTotals()->save();
            $this->_getCheckout()->saveOrder();
            $this->_getCheckout()->getQuote()->save();
            $this->_redirect('checkout/onepage/success');
        } catch (Mage_Payment_Model_Info_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            $this->_redirect('checkout/cart');
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            $this->_redirect('*/*/review', array('checkoutflow' => true));
        }
    }

    /**
     * Edit shipping address action
     */
    public function editAddressAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }

    /**
     * Edit shipping address action
     */
    public function saveAddressAction()
    {
        try {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->_getCheckout()->saveShipping($data, $customerAddressId);
            $this->_processCheckoutError($result, "shipping address");
            $this->_getQuote()->collectTotals()->save();
            $this->_redirect('*/*/review', array('checkoutflow' => true));
            return;
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }
    }

    /**
     * Action for compatibility with OneStepCheckout to allow 3D Secure for vaulted card
     * 
     * @return string
     */
    public function onestepcheckout3dsecureAction()
    {
        $helper = Mage::helper('braintree_payments');
        $nonce  = '';
        $amount = '';
        $error  = true;
        $text   = $helper->__('Try another card');

        if ($this->getRequest()->isAjax()) {
            // Next line is required to initialize Braintree
            if ($this->getRequest()->getParam('token')) {
                Mage::getModel('braintree_payments/creditcard');

                try {
                    $nonce  = $helper->getNonceForVaultedToken($this->getRequest()->getParam('token'));
                    $error  = false;
                    $text   = '';
                } catch (Exception $e) {
                    $text = $e->getMessage();
                }
            }
            if ($this->getRequest()->getParam('requestAmount')) {
                $error  = false;
                $text   = '';
                $amount = $helper->getOrderAmount();
            }
        }
        $response = array(
            'error'     => $error,
            'nonce'     => $nonce,
            'text'      => $text,
            'amount'    => $amount
        );

        $this->getResponse()->setBody(json_encode($response));
    }
}
