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
 * Pagination Toolbar Block
 */
class Kayako_Client_Block_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar {

	/**
	 * Returns Pager HTML
	 *
	 * @return string
	 */
	public function getPagerHtml()
	{
		$_pagerBlock = $this->getLayout()->createBlock('page/html_pager');

		if ($_pagerBlock instanceof Varien_Object) {

			$_pagerBlock->setAvailableLimit($this->getAvailableLimit());
			$_pagerBlock->setUseContainer(false)
				->setShowPerPage(false)
				->setShowAmounts(false)
				->setLimitVarName($this->getLimitVarName())
				->setPageVarName($this->getPageVarName())
				->setLimit($this->getLimit())
				->setCollection($this->getCollection());

			return $_pagerBlock->toHtml();
		}

		return '';
	}
}