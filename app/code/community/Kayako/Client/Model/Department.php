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
 * Department Model
 */
class Kayako_Client_Model_Department extends Kayako_Client_Model_Authentication
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
	 * Returns Department list
	 *
	 * @return array
	 */
	public function getDepartmentList()
	{
		$options_array = array();

		$departments_tree = $this->getDepartmentsTree();
		foreach ($departments_tree as $key => $val) {
			$options_array[$key] = $val['department'];
			foreach ($val['child_departments'] as $child_dept_key => $child_dept_value) {
				$options_array[$child_dept_key] = $child_dept_value;
			}
		}

		return $options_array;
	}

	/**
	 * Returns Department tree information
	 *
	 * @return array
	 */
	public function getDepartmentsTree()
	{
		$departments_tree = array();
		$all_departments  = kyDepartment::getAll()->filterByModule(kyDepartment::MODULE_TICKETS)->filterByType(kyDepartment::TYPE_PUBLIC);

		$top_departments = $all_departments->filterByParentDepartmentId(null)->orderByDisplayOrder();
		foreach ($top_departments as $top_department) {
			$departments_tree[$top_department->getId()] = array(
				'department'        => $top_department,
				'child_departments' => $all_departments->filterByParentDepartmentId($top_department->getId())->orderByDisplayOrder()
			);
		}

		return $departments_tree;
	}

	/**
	 * Retrieve Department by ID
	 *
	 * @param int $department_id
	 *
	 * @return object
	 */
	public function getDepartmentByID($department_id)
	{
		$department = kyDepartment::get($department_id);

		return $department;
	}

	/**
	 * Returns Department list
	 *
	 * @return kyResultSet
	 */
	public function getAllDepartments()
	{
		$_departments = kyDepartment::getAll();

		return $_departments;
	}
}