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
 * Index Controller to handle tickets
 */
class Kayako_Client_IndexController extends Mage_Core_Controller_Front_Action
{
	private $_action;

	/**
	 * Shows default page of Kayako Dashboard
	 *
	 * @author Amarjeet Kaur
	 */
	public function indexAction()
	{
		$this->loadLayout();
		$this->addBreadCrumbs();
		$this->_initLayoutMessages('customer/session');

		return $this->renderLayout();
	}

	/**
	 * Submit ticket page for department selection
	 *
	 * @author Amarjeet Kaur
	 *
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function submitTicketAction()
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {

			$this->loadLayout();
			$this->_action = 'submitTicket';
			$this->addBreadCrumbs();

			$_block = $this->getLayout()->getBlock('submitTicket');
			$_block->setFormAction(Mage::getUrl('*/*/submitTicketDetail'));
			$this->_initLayoutMessages('core/session');

			$_Department     = Mage::getModel("client/department");
			$_departmentList = $_Department->getDepartmentList();
			$_block->setData('_departments', $_departmentList);

			$this->renderLayout();
		} else {

			$_session = Mage::getSingleton('customer/session');

			$_session->setAfterAuthUrl(Mage::helper('core/url')->getCurrentUrl());
			$_session->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());

			$this->_redirect('customer/account/login');
		}

		return true;
	}

	/**
	 * Ticket detail Page
	 *
	 * @author Amarjeet Kaur
	 *
	 * @throws Exception If Invalid data is provided
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function submitTicketDetailAction()
	{
		$_Ticket = Mage::getModel("client/ticket");

		$this->loadLayout();
		$this->_action = 'submitTicket';
		$this->addBreadCrumbs();

		$_block = $this->getLayout()->getBlock('submitTicketDetail');
		$_block->setFormAction(Mage::getUrl('*/*/ticketSubmitted'));

		$_priorityList   = $_Ticket->getTicketPriorityList();
		$_ticketTypeList = $_Ticket->getTicketTypeList();

		$_block->setData('ticket_priorities', $_priorityList);
		$_block->setData('ticket_types', $_ticketTypeList);

		$_postData = $this->getRequest()->getPost();

		if (!empty($_postData)) {
			$_Translate = Mage::getSingleton('core/translate');
			$_Translate->setTranslateInline(false);

			try {
				$_error = false;

				if (!Zend_Validate::is(trim($_postData['department']), 'NotEmpty')) {
					$_error = true;
				}
				if ($_error) {
					throw new Exception();
				}

				$_block->setData('departmentID', $_postData['department']);
			} catch (Exception $e) {
				$_Translate->setTranslateInline(true);

				Mage::getSingleton('core/session')->addError(Mage::helper('client')->__('Please select your department.'));
				$this->_redirect('*/*/submitTicket');

				return false;
			}
		} else {
			$this->_redirect('*/*/submitTicket');
		}

		$this->renderLayout();

		return true;
	}

	/**
	 * Submit ticket details
	 *
	 * @author Amarjeet Kaur
	 *
	 * @throws Exception If Invalid data is provided
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function ticketSubmittedAction()
	{
		$_postData = $this->getRequest()->getPost();
		if ($_postData) {
			$_Translate = Mage::getSingleton('core/translate');
			$_Translate->setTranslateInline(false);

			try {
				$_error = false;

				if (!Zend_Validate::is(trim($_postData['ticketpriorityid']), 'NotEmpty')) {
					$_error = true;
				}

				if (!Zend_Validate::is(trim($_postData['tickettypeid']), 'NotEmpty')) {
					$_error = true;
				}

				if (!Zend_Validate::is(trim($_postData['ticketsubject']), 'NotEmpty')) {
					$_error = true;
				}

				if (!Zend_Validate::is(trim($_postData['ticketmessage']), 'NotEmpty')) {
					$_error = true;
				}

				if ($_error) {
					throw new Exception();
				}

				if (Mage::getSingleton('customer/session')->isLoggedIn()) {
					$_postData['ticketfullname'] = Mage::getSingleton('customer/session')->getCustomer()->getName();
					$_postData['ticketemail']    = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
				}

				$_Ticket       = Mage::getModel("client/ticket");
				$_ticketMaskID = $_Ticket->createTicket($_postData);

				if ($_ticketMaskID) {
					$this->loadLayout();
					$this->_action = 'submitTicket';
					$this->addBreadCrumbs();
					$block = $this->getLayout()->getBlock('ticketSubmitted');
					$block->setData('ticketMaskID', $_ticketMaskID);
					$block->setData('ticketDetails', $_postData);
					$this->renderLayout();
				}

				return true;
			} catch (Exception $e) {
				$_Translate->setTranslateInline(true);

				Mage::getSingleton('core/session')->addError(Mage::helper('client')->__('Unable to submit your ticket. Please, try again later'));
				$this->_redirect('*/*/submitTicket');

				return false;
			}
		} else {
			$this->_redirect('*/*/submitTicket');
		}

		return true;
	}

	/**
	 * View ticket listing
	 *
	 * @author Amarjeet Kaur
	 *
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function viewTicketAction()
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {

			$this->loadLayout();
			$this->_action = 'viewTicket';

			$this->addBreadCrumbs();
			$this->renderLayout();
		} else {
			$this->_redirect('*/*/submitTicket');
		}

		return true;
	}

	/**
	 * View ticket details
	 *
	 * @author Amarjeet Kaur
	 *
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function viewTicketDetailsAction()
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {

			$_ticketID              = $this->getRequest()->getParam('tid');
			$_Ticket                = Mage::getModel("client/ticket");
			$_ticketObjectContainer = $_Ticket->getTicketDetails($_ticketID);

			$_postData = $this->getRequest()->getPost();
			if (!empty($_postData)) {

				$_Ticket->updateTicketPriority($_ticketObjectContainer, $_postData['ticketpriorityid']);
				$_Ticket->updateTicketStatus($_ticketObjectContainer, $_postData['ticketstatusid']);

				$_ticketObjectContainer = $_Ticket->getTicketDetails($_ticketID);
			}

			$_ticket          = $_Ticket->getTicketDetailsByObject($_ticketObjectContainer);
			$_ticketPosts     = $_Ticket->getTicketPosts($_ticket['ticketid']);
			$priorityList     = $_Ticket->getTicketPriorityList();
			$ticketStatusList = $_Ticket->getTicketStatusList();

			$this->loadLayout();
			$this->_action = 'viewTicket';
			$this->addBreadCrumbs();

			$_block = $this->getLayout()->getBlock('viewTicketDetails');
			$_block->setData('ticket_priorities', $priorityList);
			$_block->setData('ticketStatusList', $ticketStatusList);
			$_block->setData('ticketDetails', $_ticket);
			$_block->setData('ticketPostDetails', $_ticketPosts);

			$this->renderLayout();
		} else {
			$this->_redirect('*/*/submitTicket');
		}

		return true;
	}

	/**
	 * Handles Ticket Reply Post
	 *
	 * @author Amarjeet Kaur
	 *
	 * @throws Exception If Invalid data is provided
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function postReplyAction()
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {

			$_ticketID = $this->getRequest()->getParam('tid');
			$_postData = $this->getRequest()->getPost();

			if (!empty($_postData)) {
				$translate = Mage::getSingleton('core/translate');
				$translate->setTranslateInline(false);

				try {
					$postObject = new Varien_Object();
					$postObject->setData($_postData);

					$_error = false;
					if (!Zend_Validate::is(trim($_postData['ticketmessage']), 'NotEmpty')) {
						$_error = true;
					}

					if ($_error) {
						throw new Exception('Please enter your message.');
					}

					$_Ticket            = Mage::getModel("client/ticket");
					$_loggedInUserEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

					if ($_Ticket->createTicketPost($_postData, $_ticketID, $_loggedInUserEmail)) {

						Mage::getSingleton('core/session')->addSuccess(Mage::helper('client')->__('Your message was submitted successfully.'));
						$this->_redirect('*/*/viewTicketDetails', array('tid' => $_ticketID));
					}

					return true;
				} catch (Exception $e) {
					$translate->setTranslateInline(true);

					Mage::getSingleton('core/session')->addError(Mage::helper('client')->__('Unable to submit your message. Please, try again later'));
					$this->_redirect('*/*/viewTicketDetails', array('tid' => $_ticketID));

					return false;
				}
			}
		} else {
			$this->_redirect('*/*/submitTicket');
		}

		return true;
	}

	/**
	 * Add Breadcrumbs in Page
	 *
	 * @author Amarjeet Kaur
	 *
	 * @return string
	 */
	public function addBreadCrumbs()
	{
		$_breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
		$_breadcrumbs->addCrumb('home', array('label' => Mage::helper('client')->__('Home'), 'title' => Mage::helper('client')->__('Home Page'), 'link' => Mage::getBaseUrl()));

		switch ($this->_action) {
			case 'submitTicket':
				$_breadcrumbs->addCrumb('submitTicket', array('label' => 'Submit Ticket', 'title' => 'Submit Ticket'));

				break;
			case 'viewTicket':
				$_breadcrumbs->addCrumb('viewTicket', array('label' => 'View Tickets', 'title' => 'View Tickets'));

				break;
			default:

				break;
		}

		return $this->getLayout()->getBlock('breadcrumbs')->toHtml();
	}

	/**
	 * Authenticate user for login share
	 *
	 * @author Amarjeet Kaur
	 *
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function authenticateAction()
	{
		$_postData = $this->getRequest()->getPost();
		if (!empty($_postData)) {
			$_session = Mage::getSingleton('customer/session');

			if ($_session->login($_postData['username'], $_postData['password'])) {

				$_fullName = $_session->getCustomer()->getName();
				$_email    = $_session->getCustomer()->getEmail();

				echo '<?xml version="1.0" encoding="UTF-8"?>
						<loginshare>
							<result>1</result>
							<user>
								<usergroup>Registered</usergroup>
								<fullname>' . $_fullName . '</fullname>
								<emails>
									<email>' . $_email . '</email>
								</emails>
							</user>
						</loginshare>';
			} else {
				echo '<?xml version="1.0" encoding="UTF-8"?>
						<loginshare>
							<result>0</result>
							<message>Invalid Username or Password</message>
						</loginshare>';
			}
		}

		return true;
	}
}