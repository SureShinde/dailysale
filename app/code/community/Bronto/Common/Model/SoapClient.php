<?php

class Bronto_Common_Model_SoapClient extends Bronto_SoapClient
{
    /**
     * Override the SoapClient for a custom SSL stream context
     *
     * @param $wsdl string
     * @param $options array
     */
    public function __construct($wsdl, array $options = array())
    {
        if (is_array($wsdl)) {
            $options = $wsdl;
            $wsdl = $wsdl['wsdl'];
            unset($options['wsdl']);
        }
        $opts = array('ciphers' => 'RC4-SHA');
        $options['stream_context'] = stream_context_create(array('ssl' => $opts));
        parent::__construct($wsdl, $options);
    }
}
