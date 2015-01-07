<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Magecron
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
        $cronPath = Mage::getBaseDir() . '/shell/bronto/cron.php';
        $phpPath = exec('which php');
        $html = "<tr><td>&nbsp;</td>
          <td id=\"bronto-magecron-example\" colspan=\"3\">
          <em style=\"margin:5px;\">* Note: You will need to change the \$CRON_USER to match the cron user in your environment.</em>
          <br/>
          <strong style=\"margin:5px;\">To setup the cron script, you will need to add a command to your crontab file.  Here are some examples:</strong>
            <div style=\"border:1px solid #ccc; padding:5px; margin:5px;\">
<strong>To run the API Send Cron every minute:</strong>
<pre>* * * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t send</pre>
<strong>To run the API Retry Cron every 2 minutes:</strong>
<pre>*/2 * * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t api</pre>
<strong>To run the Reminder Cron every 15 minutes:</strong>
<pre>*/15 * * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t reminder</pre>
<strong>To run the Order Import Cron once Daily at Midnight:</strong>
<pre>0 0 * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t order</pre>
<strong>To run the Product Recommendation Cron once Daily at Midnight:</strong>
<pre>0 0 * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t product</pre>
<strong>To run the Customer Import Cron twice Daily:</strong>
<pre>0 */2 * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t customer</pre>
<strong>To run the Newsletter Opt-In Cron every 30 minutes:</strong>
<pre>*/30 * * * * \$CRON_USER {$phpPath} {$cronPath} -a run -t newsletter</pre>
<strong>To run all Module Crons once Daily:</strong>
<pre>0 0 * * * \$CRON_USER {$phpPath} {$cronPath} -a run</pre>
</div>
</td></tr>";

        return $html . parent::_getFooterHtml($element);
    }
}
