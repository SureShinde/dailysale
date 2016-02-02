<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

require_once 'abstract.php';
class Mage_Shell_BraintreeIds extends Mage_Shell_Abstract
{
    const BULK_MAX_SIZE     = 10000;
    const MODE_ALL          = 'all';
    const MODE_CREATED_AT   = 'created_at';

    protected $_verbose;
    protected $_braintree;
    protected $_customerResource;
    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f braintreeIds.php
  help                  This help
  --mode <type>         Two possible values: "all" and "created_at". "all" runs update for all the customers, "created_at" for customers registered in Braintree starting from specific date. Default mode is "all"
  --start_date <date>   Required for mode "created_at". Date in format YYYY-MM-DD
  --verbose             Detailed output regarding process

USAGE;
    }

    /**
     * Run script
     *
     */
    public function run()
    {    
        $mode = $this->getArg('mode');
        $this->_verbose = $this->getArg('verbose') ? true : false;
        if ($mode != self::MODE_CREATED_AT) {
            $mode = self::MODE_ALL;
        }
        if ($mode == self::MODE_CREATED_AT) {
            $startDateArg = $this->getArg('start_date');
            if (!$startDateArg) {
                die ('start_date is required for mode created_at');
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $startDateArg)) {
                die ('Invalid date format. Have to be YYYY-MM-DD');
            }
            try {
                $startDate = new DateTime($startDateArg);
            } catch (Exception $e) {
                die ('Invalid date format. Have to be YYYY-MM-DD');
            }
        }

        $credentials = array();
        $websites = Mage::getResourceModel('core/website_collection')->setLoadDefault(true)->load();
        // get unique credentials for all websites
        foreach ($websites as $website) {
            $merchantId = $website->getConfig('payment/braintree/merchant_id');
            if (!array_key_exists($merchantId, $credentials)) {
                $credentials[$merchantId] = array(
                    'public_key'    => $website->getConfig('payment/braintree/public_key'),
                    'private_key'   => $website->getConfig('payment/braintree/private_key'),
                    'environment'   => $website->getConfig('payment/braintree/environment'));
            }
        }
        if (!$credentials) {
            die ('No credentials found');
        }

        $this->_braintree           = Mage::getSingleton('braintree_payments/paymentmethod');
        $this->_customerResource    = Mage::getResourceModel('customer/customer');

        foreach ($credentials as $merchantId => $additionalData) {
            Braintree_Configuration::environment($additionalData['environment']);
            Braintree_Configuration::merchantId($merchantId);
            Braintree_Configuration::publicKey($additionalData['public_key']);
            Braintree_Configuration::privateKey($additionalData['private_key']);
            $this->_output('Start processing for merchant account ' . $merchantId);
            if ($mode == self::MODE_ALL) {
                // load all Braintree customers for merchant account
                try {
                    $customers = Braintree_Customer::all();
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_output(
                        'Cannot fetch customers for merchant account ' . $merchantId . '. Verify credentials', true
                    );
                    continue;
                }
                if ($customers->maximumCount() == 0) {
                    $this->_output('No customers found for merchant account '. $merchantId);
                    continue;
                }
                if ($customers->maximumCount() < self::BULK_MAX_SIZE) {
                    $this->_processBulk($customers);
                } else {
                    $mode = self::MODE_CREATED_AT;
                    // No Braintree customers before this moment
                    $startDate = new DateTime('2007-01-01');
                }
            } 
            if ($mode == self::MODE_CREATED_AT) {
                $endDate = new DateTime();
                try {
                    $customers = Braintree_Customer::search(
                        array(Braintree_CustomerSearch::createdAt()->between($startDate, $endDate))
                    );
                } catch (Exception $e) {
                    Mage::logException($e);
                }
                if ($customers->maximumCount() == 0) {
                    $this->_output('No customers found for merchant account '. $merchantId);
                    continue;
                }
                $this->_selectBulk($startDate);
            }
            $this->_output('Processed all customers for merchant account ' . $merchantId);
        }
        $this->_output('All customers for all websites processed');
    }

    /**
     * Updates customer id
     * 
     * @param string $customerId
     * @param string $email
     */
    protected function _updateCustomer($customerId, $email, $newId = false)
    {
        if (!$newId) {
            $newId = Mage::helper('braintree_payments')->generateCustomerId($customerId, $email);
        }
        try {
            Braintree_Customer::update($customerId, array('id' => $newId));
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Processes bulk of customers
     * 
     * @param Braintree_ResourceCollection $customers
     */
    protected function _processBulk($customers)
    {
        $customersCount = $customers->maximumCount();
        $counter = 0;
        $this->_output('Selected bulk of ' . $customersCount . ' customers. Processing...');
        // Updating customer id if not updated
        foreach ($customers as $customer) {
            // If customer has email entered in Braintree than check combination email + id. 
            // If the same combination found in Magento - update id
            if ($customer->email) {
                $adapter = $this->_customerResource->getReadConnection();
                $bind    = array('customer_email' => $customer->email);
                $select  = $adapter->select()
                    ->from(
                        $this->_customerResource->getEntityTable(), 
                        array($this->_customerResource->getEntityIdField())
                    )
                    ->where('email = :customer_email');
                $customerIds = $adapter->fetchCol($select, $bind);
                foreach ($customerIds as $customerId) {
                    if ($customerId == $customer->id) {
                        $this->_updateCustomer($customerId, $customer->email);
                    }
                }
            // If customer doesn't have email entered in Braintree than search in Braintree generated customer id
            // If not found - update customer id    
            } else {
                $customerModel = Mage::getModel('customer/customer')->load($customer->id);
                if ($customerModel->getId()) {
                    $id = Mage::helper('braintree_payments')->generateCustomerId(
                        $customerModel->getId(), $customerModel->getEmail()
                    );
                    try {
                        Braintree_Customer::find($id);
                    } catch (Braintree_Exception_NotFound $e) {
                        $this->_updateCustomer($customerModel->getId(), $customerModel->getEmail(), $id);
                    } catch (Exception $e) {
                        $this->_output(
                            'Unexpected error while processing customer with email' . 
                            $customerModel->getEmail() . 'Record skipped'
                        );
                    }
                }
            }
            $counter++;
            if (($counter % 50) == 0) {
                $this->_output('Processed ' . $counter . ' customers');
            }
        }
        $this->_output('Bulk of ' . $customersCount . ' customers fully processed');
    }

    /**
     * Selects bulk of customers to process
     * 
     * @param Datetime $startDate
     * @param Datetime $endDate
     */
    protected function _selectBulk($startDate, $endDate = null)
    {
        if (is_null($endDate)) {
            $endDate = new Datetime();
        }
        try {
            $customers = Braintree_Customer::search(
                array(Braintree_CustomerSearch::createdAt()->between($startDate, $endDate))
            );
        } catch (Exception $e) {
            Mage::logException($e);
            $customers = false;
        }
        if ($customers) {
            if ($customers->maximumCount() == 0) {
                return;
            } else if ($customers->maximumCount() >= self::BULK_MAX_SIZE) {
                $this->_output(
                    'There are more than ' . self::BULK_MAX_SIZE . ' customers in interval between ' . 
                    $startDate->format('Y-m-d') . ' and ' . $endDate->format('Y-m-d') . '. Selecting smaller periods'
                );
                $customers = false;
            }
        }
        if ($customers === false) {
            $median = clone $startDate;
            $interval = new DateInterval('P' . ceil($endDate->diff($startDate)->days/2) . 'D');
            $median->add($interval);
            $this->_selectBulk($startDate, $median);
            $this->_selectBulk($median, $endDate);
        } else {
            $this->_processBulk($customers);
        }
    }

    /**
     * Outputs string if verbose option is enabled or error occupied
     * 
     * @param string $output
     * @param boolean $error
     */
    protected function _output($output, $error = false)
    {
        if ($this->_verbose || $error) {
            echo $output . '\n';
        }
        Mage::log($output, null, 'braintreeIds.log');
    }
}

$braintreeIds = new Mage_Shell_BraintreeIds();
$braintreeIds->run();
