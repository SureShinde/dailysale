<?php

/**
 * Rebuild Dynamic Categories
 *
 *
 * @author    Lucas van Staden (sales@proxiblue.com.au)
 *
 */
require_once 'abstract.php';

class Mage_Shell_RebuildDynamic extends Mage_Shell_Abstract
{

    /**
     * Runner
     */
    public function run()
    {
        if (!Mage::getStoreConfig('dyncatprod/rebuild/max_exec')) {
            ini_set('max_execution_time', 3600); // 1 hour
        }
        $start = microtime(true);
        mage::register('is_shell', true, true);
        $cronModel = mage::getModel('dyncatprod/cron');
        $fixModel = mage::getModel('dyncatprod/fixes');
        if ($this->getArg('type') == 'delayed') {
            $cronModel::rebuildDelayed(true);
        } elseif ($this->getArg('type') == 'changed') {
            $cronModel::rebuildChangedDynamic(true);
        } elseif ($this->getArg('type') == 'all') {
            $cronModel::rebuildAllDynamic(true);
        } elseif ($this->getArg('type') == 'one'
            && $this->getArg('catid')
        ) {
            $cronModel::rebuildOneDynamic(
                $this->getArg('catid'), $this->getArg('children')
            );
        } elseif ($this->getArg('fix') == 'dynamic_flag') {

        } elseif ($this->getArg('fix') == 'null_rules') {
            $fixModel::nullRules();
        } elseif ($this->getArg('fix') == 'clear_rules') {
            $fixModel::clearRules();
        } else {
            echo $this->usageHelp();
        }
        echo "\nCOMMAND TIME: " . date("H:i:s", microtime(true) - $start) . "\n";
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php rebuildDynamic.php [options]

  --type <delayed|changed|all|one --catid <category id> <--children>
        delayed: Rebuild all categories that were saved and waiting for a delayed rebuild
        changed: Rebuild all categories that have attributes in rules, that were changed in any products
        all: Like it says on the box. Rebuild the lot.
        one --catid <category id> <--children true>: rebuild one category from given id,
        include --children to build child categories

  --fix <dynamic_flag|null_rules|clear_rules>

        dynamic_flag: Scan all categories for empty rules, and ensure the products are ot set as dynamic
        null_rules: Fix rules that are supposed to be empty (null) but contained a rule stub
        clear_rules: Delete all dynamic rules! - NOT RECOVERABLE !!!! USE WITH CARE!

USAGE;
    }

}

$shell = new Mage_Shell_RebuildDynamic();
$shell->run();








