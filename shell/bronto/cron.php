<?php

// Can either be run from shell dir, modman, or cron
if (file_exists('abstract.php')) {
    require_once 'abstract.php';
} else if (preg_match('/\.modman/', dirname(__FILE__))) {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/shell/abstract.php';
} else {
    require_once dirname(dirname(dirname(__FILE__))) . '/shell/abstract.php';
}

class Bronto_Cron_Os_Script extends Mage_Shell_Abstract {

    /**
     * Cron tasks
     *
     * @var array
     */
    protected $_validCronTasks = array(
        'api',
        'send',
        'customer',
        'newsletter',
        'order',
        'product',
        'reminder',
    );

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f bronto/cron.php -- -a <action> <options>

  -h --help                 Shows usage

  --action -a <action>      Perform one of the defined actions below

Defined actions:

  list                      Shows a list of all available tasks

  run                       Runs the Cron processes
    -t --task <task>        Specifies an individual task to run

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
        if ($task == 'api') {
            $task = 'common/' . $task;
        } else if ($task == 'send') {
            return Mage::helper('bronto_common/api')->getSendName();
        }
        return Mage::helper('bronto_' . $task)->getName();
    }

    /**
     * @see run
     */
    public function run() {
        $action = $this->getArg('action') ?: $this->getArg('a');

        switch ($action) {
            case 'list':
                echo <<<LIST

Tasks:

  api           {$this->_getTaskName('api')}
  send          {$this->_getTaskName('send')}
  customer      {$this->_getTaskName('customer')}
  newsletter    {$this->_getTaskName('newsletter')}
  order         {$this->_getTaskName('order')}
  product       {$this->_getTaskName('product')}
  reminder      {$this->_getTaskName('reminder')}


LIST;
                break;
            case 'run':
                $this->_processCrons();
                break;
            default:
                $this->_showHelp();
                break;
        }
    }

    /**
     * Handle run action and get specified tasks
     */
    protected function _processCrons()
    {
        $task = $this->getArg('task') ?: $this->getArg('t');

        if (!$task) {
            $this->_runCron($this->_validCronTasks);
        } else if (in_array($task, $this->_validCronTasks)) {
            $this->_runCron(array($task));
        } else {
            $this->_showHelp();
        }

        echo "Complete\r\n\r\n";
    }

    /**
     * Run Each Specified Cron
     * @param array $tasks
     */
    protected function _runCron(array $tasks)
    {
        foreach ($tasks as $task) {
            echo "\r\n{$this->_getTaskName($task)}: Started \r\n";

            try {
                switch ($task) {
                    case 'api':
                        $result = Mage::getModel('bronto_common/observer')->processApiErrors();
                        break;
                    case 'send':
                        $result = Mage::getModel('bronto_common/observer')->processSendQueue();
                        break;
                    case 'customer':
                        $result = Mage::getModel('bronto_customer/observer')->processCustomers(true);
                        break;
                    case 'newsletter':
                        $result = Mage::getModel('bronto_newsletter/observer')->processSubscribers(true);
                        break;
                    case 'order':
                        $result = Mage::getModel('bronto_order/observer')->processOrders(true);
                        break;
                    case 'product':
                        $result = Mage::getModel('bronto_product/observer')->processContentTags();
                        break;
                    case 'reminder':
                        $result = Mage::getModel('bronto_reminder/observer')->scheduledNotification(true);
                        break;
                    default:
                        echo "\r\nInvalid Cron Task: {$this->_getTaskName($task)} \r\n\r\n";
                        return;
                }

                echo $this->_translateResults($result);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            echo "\r\n{$this->_getTaskName($task)}: Finished \r\n\r\n";
        }
    }

    /**
     * Translate the result array into a readable string
     * @param array $results
     * @return string
     */
    protected function _translateResults(array $results)
    {
        $display = '  ';
        foreach ($results as $title => $count) {
            $display .= '  ' . $title . ' = ' . $count . ';';
        }

        return $display;
    }
}

$shell = new Bronto_Cron_Os_Script();
$shell->run();
