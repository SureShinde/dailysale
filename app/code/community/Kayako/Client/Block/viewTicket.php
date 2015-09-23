<?php
/**
 * Copyright (c) 2013 Kayako
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 *OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * View Ticket Block
 */
class Kayako_Client_Block_ViewTicket extends Mage_Core_Block_Template {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$_Ticket            = Mage::getModel("client/ticket");
		$params             = $this->getRequest()->getParams();
		$_loggedInUserEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

		$_allTickets        = $_Ticket->getAllTickets($_loggedInUserEmail, $params);
		$_perPageItems      = $_Ticket->getItemsPerPage($_allTickets, $params);
		$collection         = $_Ticket->getVarienDataCollection($_perPageItems);

		$this->setCollection($collection);
	}

	/**
	 * Prepares layout
	 *
	 * @return $this
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();

		$toolbar = $this->getToolbarBlock();

		// Retrieve Collection
		$collection = $this->getCollection();

		// Retrieve available orders
		$orders = $this->getAvailableOrders();

		if (!empty($orders)) {
			$toolbar->setAvailableOrders($orders);
		}

		$sort = $this->getSortBy();
		$toolbar->setDefaultOrder($sort);

		$dir = $this->getDefaultDirection();
		$toolbar->setDefaultDirection($dir);

		$toolbar->setCollection($collection);

		$this->setChild('toolbar', $toolbar);
		$this->getCollection()->load();

		return $this;
	}

	/**
	 * Returns default direction
	 *
	 * @return string
	 */
	public function getDefaultDirection(){
        return 'asc';
    }

	/**
	 * Returns available orders
	 *
	 * @return array
	 */
	public function getAvailableOrders(){
        return array('displayticketid' =>'Ticket ID', 'lastactivity'=> 'Last Update','lastreplier'=>'Last Replier','department'=>'Department','type'=>'Type','priority'=>'Priority');
    }

	/**
	 * Returns sortby
	 *
	 * @return string
	 */
	public function getSortBy(){
        return 'displayticketid';
    }

	/**
	 * Returns toolbar block
	 *
	 * @return mixed
	 */
	public function getToolbarBlock() {
        $block = $this->getLayout()->createBlock('client/toolbar', microtime());

        return $block;
    }

	/**
	 * Returns mode
	 *
	 * @return mixed
	 */
	public function getMode() {
        return $this->getChild('toolbar')->getCurrentMode();
    }

	/**
	 * Returns toolbar HTML
	 *
	 * @return mixed
	 */
	public function getToolbarHtml() {
        return $this->getChildHtml('toolbar');
    }
}