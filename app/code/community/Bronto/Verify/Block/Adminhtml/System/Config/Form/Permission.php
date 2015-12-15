<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Permission
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return footer html for fieldset
     * Add extra tooltip comments to elements
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getFooterHtml($element)
    {
        $owner = Mage::getStoreConfig('bronto_verify/permissionchecker/owner');
        $group = Mage::getStoreConfig('bronto_verify/permissionchecker/group');
        $dir   = Mage::getStoreConfig('bronto_verify/permissionchecker/directories');
        $file  = Mage::getStoreConfig('bronto_verify/permissionchecker/files');

        if ('' != $owner || '' != $group) {
            $chown = 'sudo chown -R ' . (('' != $owner) ? $owner : '') . (('' != $group) ? ':' . $group : '') . ' ./*';
        } else {
            $chown = '';
        }

        $vMage = 'mage';
        if (Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(array('<', 5)))) {
            $vMage = 'pear';
        }

        $dPerm = ('' == $dir) ? '755' : $dir;
        $fPerm = ('' == $file) ? '644' : $file;

        $html = "<tr><td>&nbsp;</td>
            <td colspan=\"3\"><strong style=\"margin:5px;\">To apply permissions, Run These Commands in the shell at the root of your site:</strong>
            <div style=\"border:1px solid #ccc; padding:5px; margin:5px;\">
<pre>{$chown}
sudo find . -type d -exec chmod {$dPerm} {} \;
sudo find . -type f -exec chmod {$fPerm} {} \;
sudo chmod -R 777 media
sudo chmod -R 777 var
sudo chmod 550 {$vMage}
sudo chmod o+w var/.htaccess app/etc</pre>
</div></td></tr>";

        return $html . parent::_getFooterHtml($element);
    }
}
