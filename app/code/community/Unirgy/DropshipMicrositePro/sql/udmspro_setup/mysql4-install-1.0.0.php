<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('udropship/vendor'), 'confirmation', 'varchar(64)');

$nowDate = now();

$blockContent = <<<EOT
<h1 class="vendor-name">{{var currentVendorLandingPageTitle|escape:html}}</h1>
<p>{{var currentVendorReviewsSummaryHtml}}</p>
<div class="generic-box vendor-description"><img class="vendor-img" src="{{media url=\$currentVendor.getLogo()}}" alt="" /> {{var currentVendor.getDescription()|escape:html}}</div>
<div id="our-products">{{layout handle="umicrosite_current_vendor_products_list"}}</div>
EOT;
$layoutUpdateXml = <<<EOT
<reference name="left">
    <block type="catalog/layer_view" name="catalog.leftnav" before="-" template="catalog/layer/view.phtml">
        <block type="core/text_list" name="catalog.leftnav.state.renderers" as="state_renderers" />
    </block>
</reference>
<!--reference name="left_first">
    <block type="catalog/layer_view" name="catalog.leftnav" after="currency" template="catalog/layer/view.phtml">
        <block type="core/text_list" name="catalog.leftnav.state.renderers" as="state_renderers" />
    </block>
</reference-->
EOT;

if (!$conn->fetchOne("select count(*) from `{$installer->getTable('cms/page')}` where identifier='default-microsite-vendor-landing-page'")) {

    $conn->insert($installer->getTable('cms/page'), array(
        'page_id' => new Zend_Db_Expr('NULL'),
        'title' => 'Default Microsite Vendor Landing Page',
        'root_template' => 'two_columns_left',
        'meta_keywords' => '',
        'meta_description' => '',
        'identifier' => 'default-microsite-vendor-landing-page',
        'content_heading' => '',
        'content' => $blockContent,
        'creation_time' => $nowDate,
        'update_time' => $nowDate,
        'is_active' => '1',
        'sort_order' => '0',
        'layout_update_xml' => $layoutUpdateXml,
        'custom_theme' => new Zend_Db_Expr('NULL'),
        'custom_root_template' => '',
        'custom_layout_update_xml' => new Zend_Db_Expr('NULL'),
        'custom_theme_from' => new Zend_Db_Expr('NULL'),
        'custom_theme_to' => new Zend_Db_Expr('NULL')
    ));

    $installer->run("INSERT IGNORE INTO {$installer->getTable('cms/page_store')}
        (`page_id`, `store_id`) SELECT `page_id`, 0 FROM {$installer->getTable('cms/page')} where identifier='default-microsite-vendor-landing-page'"
    );

}

/*
$installer->run("INSERT INTO {$installer->getTable('cms/page_store')}
        (`page_id`, `store_id`) SELECT `_page`.`page_id`, `_store`.`store_id` FROM {$installer->getTable('cms/page')} _page,  {$installer->getTable('core/store')} _store where identifier='default-microsite-vendor-landing-page'"
    );
*/

$installer->endSetup();
