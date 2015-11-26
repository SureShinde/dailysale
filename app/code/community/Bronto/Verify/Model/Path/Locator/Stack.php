<?php

/**
 * SPL Stack implementation of XML path locator
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 */
class Bronto_Verify_Model_Path_Locator_Stack
    extends Bronto_Verify_Model_Path_Locator_IteratorAbstract
    implements Bronto_Verify_Model_Path_Locator_LocatorInterface
{
    /**
     * Gets a path to a node via SPL Stack implementation
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
        $this->_iterator->push($element->getName() . '/');
        if (!$element->getSafeParent()) {
            return $this->_iterator->pop();
        }

        return $this->getPath($element->getParent()) . $this->_iterator->pop();
    }
}
