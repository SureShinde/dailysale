<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
/*
 * Reinstall  review_setup and rating_setup
 */

$installer = $this;


$installer->startSetup();

$tableRating = array(
    $this->getTable('rating/rating'),
    $this->getTable('rating/rating_store'),
    $this->getTable('rating/rating_title'),
    $this->getTable('rating/rating_entity'),
    $this->getTable('rating/rating_option'),
    $this->getTable('rating/rating_option_vote'),
    $this->getTable('rating/rating_vote_aggregated'),
);

try{
    foreach($tableRating as $item){
       $result = $installer->getConnection()->dropTable($item);
    }
}catch (Exception $ex){
    Mage::logException($ex);
}

$tableReview = array(
    $this->getTable('review/review_store'),
    $this->getTable('review/review_aggregate'),
    $this->getTable('review/review_entity'),
    $this->getTable('review/review_status'),
    $this->getTable('review/review_detail'),
    $this->getTable('review/review'),
);

try{
    foreach($tableReview as $item){
        $result = $installer->getConnection()->dropTable($item);
    }
}catch (Exception $ex){
    Mage::logException($ex);
}

try{
    $resTable = $this->getTable('core_resource');
    $installer->getConnection()->delete($resTable,'code = \'rating_setup\'');
    $installer->getConnection()->delete($resTable,'code = \'review_setup\'');
}catch (Exception $ex){
    Mage::logException($ex);
}

$installer->endSetup();
Mage::getConfig()->cleanCache();