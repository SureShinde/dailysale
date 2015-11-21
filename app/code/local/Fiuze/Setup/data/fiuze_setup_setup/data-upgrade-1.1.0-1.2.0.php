<?php
/**
 * Fiuze Setup
 *
 * @category    Fiuze
 * @package     Setup
 * @copyright   Copyright (c) 2015 Fiuze
 * @author      ahofs
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/** @var $block Mage_Cms_Model_Block */
$block = Mage::getModel('cms/block');
$block->load('shopping_guarantee', 'identifier');
if ($block->getId()) {
    $content = <<<CONTENT
<div class="topseals">
<!-- (c) 2005, 2014. Authorize.Net is a registered trademark of CyberSource Corporation -->
<!--<div class="AuthorizeNetSeal"> <script type="text/javascript" language="javascript">var ANS_customer_id="97b9a81b-9e17-456a-a8eb-5b9bcb8a849a";</script> <script type="text/javascript" language="javascript" src="//verify.authorize.net/anetseal/seal.js" ></script>
<a href="http://www.authorize.net/" id="AuthorizeNetText" target="_blank">Credit Card Processing</a>
</div>-->

<div class="DandBseal">
    <a href="//www.dandb.com/verified/business/571190412/" target="_blank"><img src="//www.dandb.com/verified/seal/image/?t=571190412" alt="VERIFIED Seal" /></a>
</div></div>

<p>
<div class="SSLSeal">
    <span id="siteseal"><script type="text/javascript" src="https://seal.godaddy.com/getSeal?sealID=6FYkXANc29loRgBOSSjZ7Qq4sit0aUlm4pRazxsGxVMcKRH8uwaFPjd"></script></span>
</div>

<!-- McAfee Secure Trustmark for www.dailysale.com -->
<div class="McAfeeSeal">
    <a target="_blank" href="https://www.mcafeesecure.com/verify?host=www.dailysale.com"><img class="mfes-trustmark mfes-trustmark-hover" border="0" src="//cdn.ywxi.net/meter/www.dailysale.com/101.gif" width="119" height="48" title="McAfee SECURE sites help keep you safe from identity theft, credit card fraud, spyware, spam, viruses and online scams" alt="McAfee SECURE sites help keep you safe from identity theft, credit card fraud, spyware, spam, viruses and online scams" oncontextmenu="alert('Copying Prohibited by Law - McAfee Secure is a Trademark of McAfee, Inc.'); return false;"></a>
</div>
<!-- End McAfee Secure Trustmark -->

<p>
<script async type="text/javascript" src="//widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js"></script>
<div class="trustpilot-widget" data-locale="en-US" data-template-id="5418015fb0d04a0c9cf721f2" data-businessunit-id="558960ee0000ff0005806b64" data-style-height="30px" data-style-width="100%" data-theme="dark" data-stars="4,5">
</div>

<!--<script src="//cdn.ywxi.net/js/inline.js?w=120"></script>-->

<!--<div class="BBBseal">
<a target="_blank" title="Click for the Business Review of Daily  Sale Inc., an Internet Shopping in Pompano Beach FL" href="https://www.bbb.org/south-east-florida/business-reviews/internet-shopping/daily-sale-in-pompano-beach-fl-90081183#sealclick"><img alt="Click for the BBB Business Review of this Internet Shopping in Pompano Beach FL" style="border: 0;" src="https://seal-Seflorida.bbb.org/seals/blue-seal-293-61-whitetxt-dailysaleinc-90081183.png" /></a>
</div>-->

<!--<div class="BBBseal">
    <a target="_blank" <a title="Click for the Business Review of Daily  Sale Inc., an Internet Shopping in Pompano Beach FL" href="https://www.bbb.org/south-east-florida/business-reviews/internet-shopping/daily-sale-in-pompano-beach-fl-90081183#sealclick"><img alt="Click for the BBB Business Review of this Internet Shopping in Pompano Beach FL" style="border: 0;" src="https://seal-Seflorida.bbb.org/seals/blue-seal-160-82-dailysaleinc-90081183.png" /></a>-->

<!--<div class="BBBseal">
    <a target="_blank" <a title="Click for the Business Review of Daily  Sale Inc., an Internet Shopping in Pompano Beach FL" href="http://www.bbb.org/south-east-florida/business-reviews/internet-shopping/daily-sale-in-pompano-beach-fl-90081183/bbb-accreditation#sealclick"><img alt="Click for the BBB Business Review of this Internet Shopping in Pompano Beach FL" style="border: 0;" src="https://www.bbb.org/south-east-florida/images/2/cbbb-badge-horz.png" /></a>
</div>-->

<!--<script type="text/javascript">
  (function() {
    var sa = document.createElement('script'); sa.type = 'text/javascript'; sa.async = true;
    sa.src = ('https:' == document.location.protocol ? 'https://cdn' : 'http://cdn') + '.ywxi.net/js/1.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(sa, s);
  })();
</script>-->

<!--<span id="buySAFE_Kicker" name="buySAFE_Kicker" type="Kicker Guaranteed Ribbon 200x44"></span>-->
CONTENT;

    $block->setContent($content);
    try {
        $block->save();
    } catch (Exception $e) {
        Mage::logException($e);
    }
}

Mage::getConfig()->reinit();
Mage::app()->reinitStores();
Mage::dispatchEvent('adminhtml_cache_flush_all');
Mage::app()->getCacheInstance()->flush();

$installer->endSetup();