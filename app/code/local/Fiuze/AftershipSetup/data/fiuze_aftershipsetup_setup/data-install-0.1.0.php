<?php
/**
 * @category    Webinse
 * @package     Webinse_
 * @author      Alena Tsareva <alena.tsareva@webinse.com>
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$content .= <<<EOD
<div id="as-root"></div><script>(function(e,t,n){var r,i=e.getElementsByTagName(t)[0];if(e.getElementById(n))return;r=e.createElement(t);r.id=n;r.src="//apps.aftership.com/all.js";i.parentNode.insertBefore(r,i)})(document,"script","aftership-jssdk")</script>
<div class="as-track-button" data-counter="true" data-support="true" data-width="400" data-size="large"></div>
EOD;

$cmsPageData = array(
    'title' => 'Track Your Order',
    'identifier' =>'track-your-order',
    'root_template' => 'one_column',
    'stores' => array(0),
    'content' => $content
);

Mage::getModel('cms/page')->setData($cmsPageData)->save();

$installer->endSetup();
