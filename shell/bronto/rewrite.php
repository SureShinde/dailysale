<?php

// Can either be run from shell dir, modman, or cron
if (file_exists('abstract.php')) {
    require_once 'abstract.php';
} else if (preg_match('/\.modman/', dirname(__FILE__))) {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/shell/abstract.php';
} else {
    require_once dirname(dirname(dirname(__FILE__))) . '/shell/abstract.php';
}

class Bronto_Rewrite_Os_Script extends Mage_Shell_Abstract
{
    protected $_out;

    /**
     * Returns the help
     *
     * @see parent
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage: php -f bronto/rewrite.php -- <options>
  -h --help             Shows usage
  -w --with-observer    Writes Observers and event handlers as well
  -p --base-path        Use this base path

USAGE;
    }

    /**
     * Runs the rewrite program on a given base path
     *
     * @return void
     */
    public function run()
    {
        $help = $this->getArg('help') || $this->getArg('h');
        if ($help) {
            $this->_showHelp();
        } else {
            $this->_setupOutput();
            try {
                $this->_processRewrites();
            } catch (Exception $e) {
                $this->_write($e->getTraceAsString());
            }
            $this->_cleanOut();
        }
    }

    /**
     * Processes the rewrites on the all of the config.xmls in a given path
     *
     * @return void
     */
    protected function _processRewrites()
    {
        $baseIter = new RecursiveDirectoryIterator($this->_basePath());
        $recurse = new RecursiveIteratorIterator($baseIter);
        $configs = new RegexIterator($recurse, '/config\.xml$/');

        $reportsObserver = $this->_reportObserver();
        foreach ($configs as $configFile) {
            $this->_writeLine("Consuming file $configFile");
            $xml = new SimpleXMLElement($configFile, 0, true);
            $models = $xml->xpath('//models/*/rewrite');
            foreach ($models as $model) {
                foreach (get_object_vars($model) as $key => $class) {
                    $this->_writeLine("|_ Found model rewrite $key: $class");
                }
            }
            $block = $xml->xpath('//blocks/*/rewrite');
            foreach ($blocks as $block) {
                foreach (get_object_vars($block) as $key => $path) {
                    $this->_writeLine("|_ Found block rewrite $key: $path");
                }
            }
            if ($reportsObserver) {
                $events = $xml->xpath('//events');
                foreach ($events as $observers) {
                    foreach ($observers->children() as $event) {
                        $this->_writeLine("|_ Found observer(s) for {$event->getName()}:");
                        foreach ($event->xpath('observers/*') as $observer) {
                            $this->_writeLine("|__ Observer {$observer->getName()}: {$observer->class->__toString()}::{$observer->method->__toString()}");
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates the filehandle resource to be used internally
     *
     * @return Bronto_Rewrite_Os_Script
     */
    protected function _setupOutput()
    {
        $this->_out = fopen('php://output', 'w');
        return $this;
    }

    /**
     * Writes some string to the output handler
     *
     * @param string $message
     * @return Bronto_Rewrite_Os_Script
     */
    protected function _write($message)
    {
        fwrite($this->_out, $message);
        return $this;
    }

    /**
     * Writes a line of output to the output handler
     *
     * @param string $message
     * @return Bronto_Rewrite_Os_Script
     */
    protected function _writeLine($message)
    {
        return $this->_write("$message\n");
    }

    /**
     * Destroys the file handle resource
     * @return Bronto_Rewrite_Os_Script
     */
    protected function _cleanOut()
    {
        if ($this->_out) {
            fclose($this->_out);
        }
        return $this;
    }

    /**
     * Gets the base path to use for scanning
     *
     * @return string
     */
    protected function _basePath()
    {
        $basePath = $this->getArg('p') ? $this->getArg('p') : $this->getArg('base-path');
        if (empty($basePath)) {
            $basePath = '.';
        }
        return $basePath;
    }

    /**
     * Should report the observers?
     *
     * @return boolean
     */
    protected function _reportObserver()
    {
        return $this->getArg('w') || $this->getArg('with-observer');
    }
}

$shell = new Bronto_Rewrite_Os_Script();
$shell->run();
