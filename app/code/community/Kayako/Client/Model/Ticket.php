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
 * Ticket Model
 */
class Kayako_Client_Model_Ticket extends Kayako_Client_Model_Authentication
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::_construct();
		$this->init();
	}

	/**
	 * Creates a ticket
	 *
	 * @param array $data
	 *
	 * @return string $_ticketMaskID
	 */
	public function createTicket($data)
	{
		// Load department
		$objDepartment = Mage::getModel("client/department");
		$_department   = $objDepartment->getDepartmentByID($data['departmentID']);

		// Load default ticket status
		$_statusID = kyTicketStatus::getAll()->filterByType(kyTicketStatus::TYPE_PUBLIC)->first()->getId();

		kyTicket::setDefaults($_statusID, $data['ticketpriorityid'], $data['tickettypeid']);

		// Create new ticket
		$ticket = kyTicket::createNewAuto($_department, $data['ticketfullname'], $data['ticketemail'], $data['ticketmessage'], $data['ticketsubject'])->create();

		// Retrieve ticket mask id
		$_ticketMaskID = $ticket->getDisplayId();

		return $_ticketMaskID;
	}

	/**
	 * Returns Ticket Priority list
	 *
	 * @return array $_ticketPriorities
	 */
	public function getTicketPriorityList()
	{
		// Load ticket priorities
		$_ticketPriorities = kyTicketPriority::getAll()->filterByType(kyTicketPriority::TYPE_PUBLIC);

		return $_ticketPriorities;
	}

	/**
	 * Returns TicketType list
	 *
	 * @return array $_ticketTypes
	 */
	public function getTicketTypeList()
	{
		// Load ticket types
		$_ticketTypes = kyTicketType::getAll()->filterByType(kyTicketType::TYPE_PUBLIC);

		return $_ticketTypes;
	}

	/**
	 * Returns TicketStatus list
	 *
	 * @return array $ticketStatusList
	 */
	public function getTicketStatusList()
	{
		$ticketStatusList = kyTicketStatus::getAll()->filterByType(kyTicketType::TYPE_PUBLIC);

		return $ticketStatusList;
	}

	/**
	 * Retrieves all tickets
	 *
	 * @param string $userEmail
	 * @param array  $params
	 *
	 * @return array $_ticketObjectContainer
	 */
	public function getAllTickets($userEmail, $params = array())
	{
		// Load departments
		$objDepartment              = Mage::getModel("client/department");
		$_departmentObjectContainer = $objDepartment->getAllDepartments();

		// Load ticket status
		$_ticketStatusObjectContainer = $this->getTicketStatusList();

		if (empty($params['dir'])) {
			$params['dir'] = 'asc';
		}

		if (empty($params['order'])) {
			$params['order'] = 'DisplayId';
		}

		if ($params['dir'] == 'desc') {
			$_sortBy = false;
		} else {
			$_sortBy = true;
		}

		$_orderByFunction = $this->getOrderByFunctionName($params['order']);

		// Load all tickets
		$_ticketObjectContainer = kyTicket::getAll($_departmentObjectContainer, $_ticketStatusObjectContainer)->filterByEmail($userEmail)->$_orderByFunction($_sortBy);

		return $_ticketObjectContainer;
	}

	/**
	 * Returns items per page
	 *
	 * @param array $_ticketObjectContainer
	 * @param array $params
	 *
	 * @return array $_ticketContainer
	 */
	public function getItemsPerPage($_ticketObjectContainer, $params)
	{
		if (empty($params['limit'])) {
			$params['limit'] = 10;
		}

		if (empty($params['p'])) {
			$params['p'] = 1;
		}

		$_page            = $_ticketObjectContainer->getPage($params['p'], $params['limit']);
		$_ticketContainer = $this->getTicketArray($_page);

		return $_ticketContainer;
	}

	/**
	 * Returns OrderBy function name
	 *
	 * @param string $_orderBy
	 *
	 * @return string $_orderByFunction
	 */
	public function getOrderByFunctionName($_orderBy)
	{
		$_orderByFunction = 'orderBy';

		switch ($_orderBy) {
			case 'lastactivity':
				$parameter = 'LastActivity';
				break;
			case 'lastreplier':
				$parameter = 'LastReplier';
				break;
			case 'department':
				$parameter = 'DepartmentId';
				break;
			case 'type':
				$parameter = 'TypeId';
				break;
			case 'priority':
				$parameter = 'PriorityId';
				break;
			default:
				$parameter = 'DisplayId';
				break;
		}

		$_orderByFunction .= $parameter;

		return $_orderByFunction;
	}

	/**
	 * Returns Ticket array from object
	 *
	 * @param array $_ticketObjectContainer
	 *
	 * @return array $_ticketContainer
	 */
	public function getTicketArray($_ticketObjectContainer)
	{
		$_ticketContainer = array();

		foreach ($_ticketObjectContainer as $_ticketObject) {
			$_ticket                                   = $this->getTicketDetailsByObject($_ticketObject);
			$_ticketContainer[$_ticketObject->getId()] = $_ticket;
		}

		return $_ticketContainer;
	}

	/**
	 * Returns Ticket details from object
	 *
	 * @param object $_ticketObject
	 *
	 * @return array $_ticket
	 */
	public function getTicketDetailsByObject($_ticketObject)
	{
		$_ticket = array();

		$_ticket['ticketid']        = $_ticketObject->getId();
		$_ticket['displayticketid'] = $_ticketObject->getDisplayId();
		$_ticket['departmentid']    = $_ticketObject->getDepartmentId();
		$_ticket['department']      = $_ticketObject->getDepartment()->getTitle();
		$_ticket['ticketstatusid']  = $_ticketObject->getStatusId();
		$_ticket['status']          = $_ticketObject->getStatus()->getTitle();
		$_ticket['statusbgcolor']   = $_ticketObject->getStatus()->getStatusBackgroundColor();
		$_ticket['priorityid']      = $_ticketObject->getPriorityId();
		$_ticket['priority']        = $_ticketObject->getPriority()->getTitle();
		$_ticket['prioritybgcolor'] = $_ticketObject->getPriority()->getBackgroundColor();
		$_ticket['userid']          = $_ticketObject->getUserId();
		$_ticket['tickettypeid']    = $_ticketObject->getTypeId();
		$_ticket['type']            = $_ticketObject->getType()->getTitle();
		$_ticket['userid']          = $_ticketObject->getUserId();
		$_ticket['fullname']        = $_ticketObject->getFullName();
		$_ticket['email']           = $_ticketObject->getEmail();
		$_ticket['ownerstaffid']    = $_ticketObject->getOwnerStaffId();
		$_ticket['lastreplier']     = $_ticketObject->getLastReplier();
		$_ticket['subject']         = $_ticketObject->getSubject();
		$_ticket['dateline']        = $_ticketObject->getCreationTime();
		$_ticket['lastactivity']    = $_ticketObject->getLastActivity();

		return $_ticket;
	}

	/**
	 * Returns Ticket details
	 *
	 * @param int $_ticketID
	 *
	 * @return kyTicket
	 */
	public function getTicketDetails($_ticketID)
	{
		$_ticketObjectContainer = kyTicket::get($_ticketID);

		return $_ticketObjectContainer;
	}

	/**
	 * Returns Ticket Posts
	 *
	 * @param int $_ticketID
	 *
	 * @return array $_ticketPostContainer
	 */
	public function getTicketPosts($_ticketID)
	{
		$_ticketPostObjectContainer = kyTicketPost::getAll($_ticketID);
		$_ticketPostContainer       = $this->getTicketPostArray($_ticketPostObjectContainer);

		return $_ticketPostContainer;
	}

	/**
	 * Returns Ticket Posts array
	 *
	 * @param object $_ticketPostObjectContainer
	 *
	 * @return array $_ticketPostContainer
	 */
	public function getTicketPostArray($_ticketPostObjectContainer)
	{
		$_ticketPostContainer = array();

		foreach ($_ticketPostObjectContainer as $_ticketPostObject) {
			$_ticketPost['ticketpostid']    = $_ticketPostObject->getId();
			$_ticketPost['ticketid']        = $_ticketPostObject->getTicketId();
			$_ticketPost['dateline']        = $_ticketPostObject->getDateline();
			$_ticketPost['userid']          = $_ticketPostObject->getUserId();
			$_ticketPost['fullname']        = $_ticketPostObject->getFullName();
			$_ticketPost['email']           = $_ticketPostObject->getEmail();
			$_ticketPost['emailto']         = $_ticketPostObject->getEmailTo();
			$_ticketPost['subject']         = $_ticketPostObject->getSubject();
			$_ticketPost['ipaddress']       = $_ticketPostObject->getIPAddress();
			$_ticketPost['hasattachments']  = $_ticketPostObject->getHasAttachments();
			$_ticketPost['creator']         = $_ticketPostObject->getCreatorType();
			$_ticketPost['isthirdparty']    = $_ticketPostObject->getIsThirdParty();
			$_ticketPost['ishtml']          = $_ticketPostObject->getIsHTML();
			$_ticketPost['isemailed']       = $_ticketPostObject->getIsEmailed();
			$_ticketPost['staffid']         = $_ticketPostObject->getStaffId();
			$_ticketPost['contents']        = $_ticketPostObject->getContents();
			$_ticketPost['issurveycomment'] = $_ticketPostObject->getIsSurveyComment();

			$_creatorLabel = 'user';
			$_badgeText    = '';
			if ($_ticketPost['isthirdparty'] == '1' || $_ticketPost['creator'] == kyTicketPost::CREATOR_THIRDPARTY) {
				$_badgeText    = 'Third Party';
				$_creatorLabel = 'thirdparty';
			} else if ($_ticketPost['creator'] == kyTicketPost::CREATOR_CC) {
				$_badgeText    = 'Recipient';
				$_creatorLabel = 'cc';
			} else if ($_ticketPost['creator'] == kyTicketPost::CREATOR_BCC) {
				$_badgeText    = 'BCC';
				$_creatorLabel = 'bcc';
			} else if ($_ticketPost['creator'] == kyTicketPost::CREATOR_USER) {
				$_badgeText    = 'User';
				$_creatorLabel = 'user';
			} else if ($_ticketPost['creator'] == kyTicketPost::CREATOR_STAFF) {
				$_badgeText    = 'Staff';
				$_creatorLabel = 'staff';
			}

			$_ticketPost['creatorlabel'] = $_creatorLabel;

			$_postTitle               = 'Posted on: ' . $_ticketPost['dateline'];
			$_ticketPost['posttitle'] = $_postTitle;
			$_ticketPost['badgetext'] = $_badgeText;

			$_ticketPostMinimumHeight     = 300;
			$_ticketPost['minimumheight'] = $_ticketPostMinimumHeight;

			$_ticketPostContainer[$_ticketPost['ticketpostid']] = $_ticketPost;
		}

		return $_ticketPostContainer;
	}

	/**
	 * Updates Ticket Priority
	 *
	 * @param object $_ticketObjectContainer
	 * @param int    $priorityID
	 */
	public function updateTicketPriority($_ticketObjectContainer, $priorityID)
	{
		$_ticketObjectContainer->setPriorityId($priorityID)->update();
	}

	/**
	 * Updates ticket status
	 *
	 * @param object $_ticketObjectContainer
	 * @param int    $statusID
	 */
	public function updateTicketStatus($_ticketObjectContainer, $statusID)
	{
		$_ticketObjectContainer->setStatusId($statusID)->update();
	}

	/**
	 * Creates a new Ticket Post
	 *
	 * @param array  $data
	 * @param int    $_ticketID
	 * @param string $userEmail
	 *
	 * @return object $_ticketPost
	 */
	public function createTicketPost($data, $_ticketID, $userEmail)
	{
		$_User         = Mage::getModel("client/user");
		$_userDetails  = $_User->getUser($userEmail);
		$_ticketObject = $this->getTicketDetails($_ticketID);
		$_ticketPost   = kyTicketPost::createNew($_ticketObject, $_userDetails, $data['ticketmessage'])->create();

		return $_ticketPost;
	}
}