<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
/*
 * Reinstall  review_setup and rating_setup
 */

$installer = $this;
$content .= <<<EOD
<div id="as-root"></div><script>(function(e,t,n){var r,i=e.getElementsByTagName(t)[0];if(e.getElementById(n))return;r=e.createElement(t);r.id=n;r.src="//button.aftership.com/all.js";i.parentNode.insertBefore(r,i)})(document,"script","aftership-jssdk")</script>
<div class="as-track-button" data-size="large" data-domain="tracking.dailysale.com"></div>
EOD;

$installer->startSetup();

$page = Mage::getModel('cms/page')->setStoreId(Mage::app()->getStore()->getId())->load('track-your-order');
$page->setContent($content);
try{
    $page->save();
}catch (Exception $ex){
    Mage::logException($ex);
}

$installer->endSetup();
