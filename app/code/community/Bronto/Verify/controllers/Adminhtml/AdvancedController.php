<?php

/**
 * API Token Validation Controller
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Adminhtml_AdvancedController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var array
     */
    private $_events = array();

    /**
     * Gets Observers for specified Event.
     *
     * @return $this|array
     */
    public function AjaxobserversAction()
    {
        $eventName = $this->getRequest()->getPost('event', false);

        // If no event name provided,
        if (!$eventName) {
            return array();
        }

        // Ensure Event Name is all lowercase
        $eventName = strtolower($eventName);

        // Set up $areas array and $observers array
        $areas     = array('global', 'adminhtml', 'frontend');
        $observers = array();

        // Cycle Through Areas and get config information
        foreach ($areas as $area) {
            $eventConfig = Mage::app()->getConfig()->getEventConfig($area, $eventName);

            if (!$eventConfig) {
                $this->_events[$area][$eventName] = false;
                continue;
            }

            try {
                foreach ($eventConfig->observers->children() as $obsName => $obsConfig) {
                    $methodReflection    = new ReflectionMethod($obsConfig->getClassName(), $obsConfig->method);
                    $observers[$obsName] = array(
                        'area'   => $area,
                        'type'   => (string)$obsConfig->type,
                        'model'  => $obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName(),
                        'class'  => $obsConfig->getClassName(),
                        'method' => (string)$obsConfig->method,
                        'line'   => $methodReflection->getStartLine(),
                        'path'   => $this->getClassPath($obsConfig->getClassName()),
                    );
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        // Create Block to Display Results
        $block = Mage::app()->getLayout()->createBlock('bronto_verify/adminhtml_system_config_advanced_observersearch');
        $block->setObservers($observers);

        // Set Response Body
        $this->getResponse()->setBody($block->toHtml());

        return $this;
    }

    /**
     * Get File Path for Specified Class
     *
     * @return string
     */
    public function AjaxclasspathAction()
    {
        // Get Params
        $className = $this->getRequest()->getPost('class', false);
        $classType = $this->getRequest()->getPost('type', 'model');

        // If no class specified, return empty
        if (!$className) {
            return '';
        }

        $path = '<div style="border:1px solid #ccc; padding: 3px 5px; margin: 3px 0;"><strong>Path :</strong> ' . $this->getClassPath($className, $classType) . '</div>';

        // Get and return class Path
        $this->getResponse()->setBody($path);

        return $this;
    }

    /**
     * Get File Path for specified Class
     *
     * @param        $class
     * @param string $type model or helper
     *
     * @return string
     */
    public function getClassPath($class, $type = 'model')
    {
        // If helper and no / present, assume data helper
        if (preg_match('/^[a-z0-9]+(_[a-z0-9]*)?$/i', $class) && $type == 'helper') {
            $class .= '/data';
        }

        // If class Alias
        if (preg_match('/^[a-z0-9]+(_[a-z0-9]*)?\/[a-z0-9_]+$/i', $class)) {
            $class = strtolower($class);

            switch ($type) {
                case 'helper':
                    $object = Mage::helper($class);
                    break;
                case 'model':
                default:
                    $object = Mage::getModel($class);
                    break;
            }

            $class = get_class($object);
        }

        // Set Type
        $type = ucfirst($type);

        // Get Details from Class
        $classDetails = explode('_', $class);
        $moduleName   = array_shift($classDetails) . '_' . array_shift($classDetails);
        $modulePath   = Mage::getModuleDir($type, $moduleName);

        // Handle Controllers Different Folder Setup
        if ($type == 'controller' || preg_match('/[a-zA-Z]*Controller$/i', $classDetails[count($classDetails) - 1])) {
            $classDetails[count($classDetails) - 1] = str_replace('controller', 'Controller', $classDetails[count($classDetails) - 1]);
            array_unshift($classDetails, 'controllers');
        }

        // Implode all Parts with slash to build rest of path
        $file = implode('/', $classDetails);

        // Build Path String
        $filePath = "$modulePath/$file.php";

        // If file doesn't exist, don't show
        if (file_exists($filePath)) {
            return $filePath;
        }

        return 'File Does Not Exist!';
    }
}