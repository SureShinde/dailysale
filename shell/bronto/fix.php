<?php

// Can either be run from shell dir, modman, or cron
if (file_exists('abstract.php')) {
    require_once 'abstract.php';
} else if (preg_match('/\.modman/', dirname(__FILE__))) {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/shell/abstract.php';
} else {
    require_once dirname(dirname(dirname(__FILE__))) . '/shell/abstract.php';
}

class Bronto_Fix_Os_Script extends Mage_Shell_Abstract {

    /**
     * Cron tasks
     *
     * @var array
     */
    protected $_validTasks = array(
        'common',
        'customer',
        'email',
        'emailcapture',
        'news',
        'newsletter',
        'order',
        'product',
        'reminder',
        'reviews',
    );

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f bronto/fix.php -- -a <action> <options>

  -h --help                 Shows usage

  --action -a <action>      Perform one of the defined actions below

Defined actions:

  list                      Shows a list of all available tasks

  run                       Runs the Fix process to trigger DB reinstall for specified/all modules
    -t --task <task>        Specifies an individual task to run.  If not specified, reset will happen on all Bronto Modules
    -r --revert <version>   Specifies a module version to replace the core_resource entry with.
                            This will allow for simply upgrading the module from a specified version.  Will cause exception if table changes already exist.
    -f --full               Removes all Bronto Module entries in core_resource table forcing a reinstall, and removes all
                            core_config_data entries for the specified module(s) which will clear any previously set configurations

USAGE;
    }

    /**
     * Get Human Readable name of Cron Task
     *
     * @param  string $task
     * @return string
     */
    protected function _getTaskName($task)
    {
        return Mage::helper('bronto_' . $task)->getName();
    }

    /**
     * @see run
     */
    public function run() {
        $action = $this->getArg('action') ? $this->getArg('action') : $this->getArg('a');

        switch ($action) {
            case 'list':
                echo <<<LIST

Tasks:

  all           All Bronto Extension Modules
  common        {$this->_getTaskName('common')}
  customer      {$this->_getTaskName('customer')}
  email         {$this->_getTaskName('email')}
  emailcapture  {$this->_getTaskName('emailcapture')}
  news          {$this->_getTaskName('news')}
  newsletter    {$this->_getTaskName('newsletter')}
  order         {$this->_getTaskName('order')}
  product       {$this->_getTaskName('product')}
  reminder      {$this->_getTaskName('reminder')}
  reviews       {$this->_getTaskName('reviews')}


LIST;
                break;
            case 'run':
                $this->_processReset();
                break;
            default:
                $this->_showHelp();
                break;
        }
    }

    /**
     * Handle run action and get specified tasks
     */
    protected function _processReset()
    {
        $task   = $this->getArg('task') ?: $this->getArg('t');
        $full   = $this->getArg('full') ?: $this->getArg('f');
        $revert = $this->getArg('revert') ?: $this->getArg('r');

        if (!$task || $task == 'all') {
            $this->_runReset($this->_validTasks, $full, false);
        } else if (in_array($task, $this->_validTasks)) {
            $this->_runReset(array($task), $full, $revert);
        } else {
            $this->_showHelp();
        }

        echo "Complete\r\n\r\n";
    }

    /**
     * Run Each Specified Reset
     * @param array $tasks
     * @param bool  $full
     * @param mixed $revert
     */
    protected function _runReset(array $tasks, $full = false, $revert = false)
    {
        foreach ($tasks as $task) {
            echo "\r\n{$this->_getTaskName($task)}: Started \r\n";

            try {
                if ($revert) {
                    $this->_revert($task, $revert);
                } else {
                    $this->_reset($task, $full, $revert);
                }

                if ($full) {
                    $this->_clearConfig($task);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            echo "{$this->_getTaskName($task)}: Finished \r\n\r\n";
        }
    }

    /**
     * Replaces version in DB with specified version
     * @param $target
     * @param $version
     */
    protected function _revert($target, $version)
    {
        // If no version specified, get version
        if ($version === true) {
            $version = $this->_getRevertVersion($target);
        }

        if (!$version) {
            return;
        }

        // Update Core Resource Table
        $resource = Mage::getSingleton('core/resource');
        $write    = $resource->getConnection('core_write');
        $table    = $resource->getTableName('core_resource');
        $query    = "UPDATE {$table} SET `version` = '{$version}', `data_version` = '{$version}' WHERE `code` = 'bronto_{$target}_setup'";
        $write->query($query);

        echo '    Core Resource entry reverted to ' . $version . ' for ' . $this->_getTaskName($target) . "\r\n";
    }

    /**
     * Handles Resetting Modules
     * @param string $target
     */
    protected function _reset($target)
    {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $write->delete($resource->getTableName('core_resource'), "code = 'bronto_{$target}_setup'");

        echo '    Core Resource entry removed for ' . $this->_getTaskName($target) . "\r\n";
    }

    /**
     * Clears Core_Config_Data for module
     * @param string $target
     */
    protected function _clearConfig($target)
    {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $write->delete($resource->getTableName('core_config_data'), "path LIKE 'bronto_{$target}%'");

        echo '    Core Config Data entries removed for ' . $this->_getTaskName($target) . "\r\n";
    }

    /**
     * Get Module Version, decrement patch by one and return
     *
     * @param $target
     *
     * @return string
     */
    protected function _getRevertVersion($target)
    {
        $moduleName = 'Bronto_' . ucfirst($target);
        $helper = Mage::helper('bronto_common');
        if (method_exists($helper, 'getModuleVersion')) {
            $curVersion = $helper->getModuleVersion($moduleName);
        } else {
            $modules = (array) Mage::getConfig()->getNode('modules')->children();
            $curVersion = isset($modules[$moduleName]) ? (string) $modules[$moduleName]->version : NULL;
        }

        // If no curVersion, we have an issue
        if (!$curVersion) {
            echo '    ! Could not get Current module version for ' . $target;
            return false;
        }

        $parts = explode('.', $curVersion);
        $patch = array_pop($parts);
        $patch--;
        $parts[] = $patch;
        $revVersion = implode('.', $parts);

        return $revVersion;
    }
}

$shell = new Bronto_Fix_Os_Script();
$shell->run();
