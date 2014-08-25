<?php
/**
 * Authorize.Net CIM - Core bug fix.
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Having a problem with the plugin?
 * Not sure what something means?
 * Need custom development?
 * Give us a call!
 *
 * @category    ParadoxLabs
 * @package     ParadoxLabs_AuthorizeNetCim
 * @author      Ryan Hoerr <ryan@paradoxlabs.com>
 */

/**
 * Simple function overload to support admin creation
 * of recurring profile/nominal item orders.
 *
 * This function is deprecated, but they did it wrong.
 *
 * arg.
 */

class ParadoxLabs_AuthorizeNetCim_Model_Quote extends Mage_Sales_Model_Service_Quote
{
    /**
     * @deprecated after 1.4.0.1
     * @see submitOrder()
     * @see submitAll()
     */
    public function submit()
    {
        // return $this->submitOrder();
        $this->submitAll();

        return $this->getOrder();
    }
}
