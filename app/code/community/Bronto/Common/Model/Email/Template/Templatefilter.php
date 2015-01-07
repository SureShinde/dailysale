<?php

class Bronto_Common_Model_Email_Template_Templatefilter extends Mage_Core_Model_Email_Template_Filter
{


    public function blockDirective($construction)
    {
        $params = $this->_getIncludeParameters($construction[2]);
        switch ($params['template']) {
            case 'email/order/shipment/track.phtml':
                return $this->_addBrontoStyle('shipmentTracking');
            default:
                return '';
        }

    }

    /**
     *
     * @param array $construction
     *
     * @return string
     */
    public function layoutDirective($construction)
    {
        $params = $this->_getIncludeParameters($construction[2]);
        switch ($params['handle']) {
            case 'sales_email_order_shipment_items':
                $return = 'shipmentItems';
                break;
            case 'sales_email_order_items':
                $return = 'orderItems';
                break;
            case 'sales_email_order_creditmemo_items':
                $return = 'creditmemoItems';
                break;
            case 'sales_email_order_invoice_items':
                $return = 'invoiceItems';
                break;
            default:
                return '';
                break;
        }

        return $this->_addBrontoStyle($return);
    }

    /**
     * Retrieve block parameters
     *
     * @param mixed $value
     *
     * @return array
     */
    protected function _getBlockParameters($value)
    {
        $tokenizer = new Varien_Filter_Template_Tokenizer_Parameter();
        $tokenizer->setString($value);

        return $tokenizer->tokenize();
    }

    /**
     * Retrieve store URL directive
     * Support url and direct_url properties
     *
     * @param array $construction
     *
     * @return string
     */
    public function storeDirective($construction)
    {
        $params = $this->_getIncludeParameters($construction[2]);

        if (isset($params['direct_url'])) {
            return $this->_addBrontoStyle('storeurl_' . str_replace('/', '_', $params['direct_url']));
        } elseif (isset($params['url'])) {
            switch (trim($params['url'], '/')) {
                case 'checkout/cart':
                    return $this->_addBrontoStyle('cartURL');
                case 'wishlist/wishlist':
                case 'wishlist/index':
                case 'wishlist/index/index':
                case 'wishlist':
                    return $this->_addBrontoStyle('wishlistURL');
                case 'customer/account':
                    return $this->_addBrontoStyle('customerURL');
                case 'adminhtml/index/resetpassword':
                    return $this->_addBrontoStyle('adminPasswordResetLink');
                case 'customer/account/resetpassword':
                    return $this->_addBrontoStyle('passwordResetLink');
                case 'customer/account/confirm':
                    return $this->_addBrontoStyle('confirmationLink');
                case '':
                    return $this->_addBrontoStyle('storeURL');
                default:
                    return $this->_addBrontoStyle('storeurl_' . str_replace('/', '_', $params['url']));
            }
        } else {
            return $this->_addBrontoStyle('storeURL');
        }
    }

    /**
     * Directive for converting special characters to HTML entities
     * Supported options:
     *     allowed_tags - Comma separated html tags that have not to be converted
     *
     * @param array $construction
     *
     * @return string
     */
    public function htmlescapeDirective($construction)
    {
        if (strstr($construction[2], 'var') === false) {
            return '';
        }
        $returnVariable = $this->processVariable(str_replace('var=$', '', $construction[2]));

        return $this->_addBrontoStyle($returnVariable);
    }

    /**
     * Var directive with modifiers support
     *
     * @param array $construction
     *
     * @return string
     */
    public function varDirective($construction)
    {
        $returnVariable = $this->processVariable($construction[2]);

        return $this->_addBrontoStyle($returnVariable);
    }

    protected function processVariable($variable)
    {

        switch (trim($variable)) {
            case "order.getCreatedAtFormated('long')":
                return 'orderCreatedAt';
            case "rma.getCreatedAtFormated('long')":
                return 'rmaCreatedAt';
            case "logo_url":
                return 'emailLogo';
            case "user.name":
                return 'adminName';
            case "subscriber.getConfirmationLink()":
                return 'subConfirmationLink';
        }
        $parts = explode('|', $variable, 2);
        if (2 === count($parts)) {
            list($returnVariable, $modifiersString) = $parts;
        } else {
            $returnVariable = $variable;
        }
        $parts = explode('.', $returnVariable);
        foreach ($parts as $i => $part) {
            if (stripos($part, 'get') === 0) {
                $parts[$i] = str_replace('get', '', $parts[$i]);
                $parts[$i] = str_replace('()', '', $parts[$i]);
            }
            if (stripos($part, 'format') === 0) {
                unset($parts[$i]);
            }
        }

        return implode('_', $parts);
    }

    /**
     * HTTP Protocol directive
     *
     * Using:
     * {{protocol}} - current protocol http or https
     * {{protocol url="www.domain.com/"}} domain URL with current protocol
     * {{protocol http="http://url" https="https://url"}
     * also allow additional parameter "store"
     *
     * @param array $construction
     *
     * @return string
     */
    public function protocolDirective($construction)
    {
        return '';
    }

    /**
     * Store config directive
     *
     * @param array $construction
     *
     * @return string
     */
    public function configDirective($construction)
    {
        $params = $this->_getIncludeParameters($construction[2]);

        switch ($params['path']) {
            case 'trans_email/ident_support/email':
                $returnValue = 'supportEmail';
                break;
            case 'general/store_information/phone':
                $returnValue = 'supportPhone';
                break;
            case 'trans_email/ident_sales/email':
                $returnValue = 'salesEmail';
                break;
            default:
                $returnValue = str_replace('/', '_', $params['path']);
                break;
        }

        return $this->_addBrontoStyle($returnValue);
    }

    /**
     * Filter the string as template.
     * Rewritten for logging exceptions
     *
     * @param string $value
     *
     * @return string
     */
    public function filter($value)
    {
        try {
            $value = parent::filter($value);
        } catch (Exception $e) {
            $value = '';
            Mage::logException($e);
        }

        return $value;
    }

    public function includeDirective($construction)
    {
        return '';
    }

    public function dependDirective($construction)
    {
        return $this->filter($construction[2]);
    }

    public function ifDirective($construction)
    {
        return '';
    }

    protected function _camelize($name)
    {
        return $this->_lcfirst(uc_words($name, ''));
    }

    protected function _lcfirst($string)
    {
        if (function_exists('lcfirst') !== false) {
            return lcfirst($string);
        } else {
            if (!empty($string)) {
                $string{0} = strtolower($string{0});
            }
        }

        return $string;
    }

    protected function _addBrontoStyle($string)
    {
        return '%%#' . $this->_camelize($string) . '%%';
    }
}
