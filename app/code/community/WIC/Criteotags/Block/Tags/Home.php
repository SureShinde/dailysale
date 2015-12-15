<?php

/**
 * Web In Color
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file WIC-LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.webincolor.fr/WIC-LICENSE.txt
 *
 * @package		WIC_Criteotags
 * @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
 * @author		Web In Color <contact@webincolor.fr>
 * */
class WIC_Criteotags_Block_Tags_Home extends Mage_Core_Block_Abstract {

    protected function _toHtml() {
        $html = '';
        if (Mage::helper('criteotags')->isTagsEnabled()) {
            $html .= '<script type="text/javascript">';
            $html .= 'window.criteo_q = window.criteo_q || [];';
            $html .= 'window.criteo_q.push(';
            $html .= '{ event: "setAccount", account: ' . Mage::helper('criteotags')->getAccountId() . '},';
            $html .= '{ event: "setSiteType", type: "' . Mage::helper('criteotags')->getSitetype() . '"},';

            if (Mage::helper('criteotags')->getCustomerId()) {
                $html .= '{ event: "setCustomerId", id: ' . Mage::helper('criteotags')->getCustomerId() . '},';
                $html .= '{ event: "setHashedEmail", email: ["' . Mage::helper('criteotags')->getHashedEmail() . '"] },';
            }

            $html .= '{ event: "viewHome"}';
            $html .= ');';
            $html .='</script>';
        }

        return $html;
    }

}
