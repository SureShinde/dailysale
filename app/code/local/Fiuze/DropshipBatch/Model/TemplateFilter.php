<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_DropshipBatch
 * @copyright Copyright (c) 2016 Fiuze
 */

class Fiuze_DropshipBatch_Model_TemplateFilter extends Unirgy_DropshipBatch_Model_TemplateFilter
{
    public function varDirective($construction)
    {
        $value = parent::varDirective($construction);
        $value = str_replace('"', '""', $value);
        foreach ($construction as $item) {
            if ($item ==  ' order.created_at') {
                $date = new Zend_Date($value);
                $timezone = Mage::getStoreConfig('general/locale/timezone');
                $date->setTimezone($timezone);
                return $date->toString();
            }
        }

        return $value;
    }
}