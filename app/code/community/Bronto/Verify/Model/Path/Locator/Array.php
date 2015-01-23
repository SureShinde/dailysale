<?php

/**
 * Array implementation of XML path locator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Model_Path_Locator_Array
    extends Bronto_Verify_Model_Path_Locator_IteratorAbstract
    implements Bronto_Verify_Model_Path_Locator_LocatorInterface
{
    /**
     * Gets a path to a node via array implementation
     *
     * Pass in the child node and will recurse up the XML tree to print out
     * the path in the tree to that node
     *
     * <config>
     *   <path>
     *     <to>
     *       <node>
     *         Node Value
     *       </node>
     *     </to>
     *   </path>
     * </config>
     *
     * If you pass in the "node" object, this will print out
     * config/path/to/node/
     *
     * @param SimpleXmlElement $element Child element to find path to
     *
     * @return string
     * @access public
     */
    public function getPath(SimpleXmlElement $element)
    {
        $this->_iterator[] = $element->getName() . '/';
        if (!$element->getSafeParent()) {
            return array_pop($this->_iterator);
        }

        return $this->getPath($element->getParent()) . array_pop($this->_iterator);
    }
}
