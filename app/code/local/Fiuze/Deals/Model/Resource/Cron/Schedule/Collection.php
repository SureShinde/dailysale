<?php

class Fiuze_Deals_Model_Resource_Cron_Schedule_Collection extends Mage_Cron_Model_Resource_Schedule_Collection
{
    /**
     * Implementation of IteratorAggregate::getIterator()
     * Sort order for cron jobs
     */
    public function getIterator()
    {
        $this->load();
        $array = $this->_items;
        foreach ($array as $item) {
            if ($item->getJobCode() == 'fiuze_deals_scheduler') {
                $item->setSortOrder(1);
            } else {
                $item->setSortOrder(0);
            }
        }
        return uasort($array, array($this, 'cmp')) ? new ArrayIterator($array) : new ArrayIterator($this->_items);
    }

    public function cmp($a, $b)
    {
        if ($a->getSortOrder() == $b->getSortOrder()) {
            return 0;
        }
        return ($b->getSortOrder() < $a->getSortOrder()) ? -1 : 1;
    }
}