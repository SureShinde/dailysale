<?php

class Bronto_News_Block_Adminhtml_Redirect
    extends Mage_Adminhtml_Block_Template
{
    private $_url;

    protected function _toHtml()
    {
        $html = "You are about to be redirected off site to:<br /><br />";
        $html .= "<a href=\"{$this->_url}\">{$this->_url}</a>";

        return $html;
    }

    public function setRedirectUrl($url)
    {
        $this->_url = $url;

        return $this;
    }

    public function getRedirectUrl()
    {
        return $this->_url;
    }
}