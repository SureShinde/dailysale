<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

require_once(__DIR__ . '/../abstract.php');

/**
 * LSC CLI interface.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Shell_Feed extends Mage_Shell_Abstract
{
    /**
     *
     * @return boolean
     */
    public function run()
    {
        try {
            /** @var $feedManager SLI_Search_Model_FeedManager */
            $feedManager = Mage::getModel('sli_search/FeedManager');

            if ($this->getArg('build')) {
                $id = $this->getArg('store');
                if (!is_bool($id)) {
                    $feedManager->generateFeedForStores(array($id));
                    echo "Feed generated for store $id.\n";

                    return true;
                } else {
                    if (count($this->_args) == 1) {
                        $feedManager->generateFeedForStores();
                        echo "Generating feeds for all stores.\n";

                        return true;
                    }
                }
            } elseif ($this->getArg('cron')) {
                /** @var $cron SLI_Search_Model_Cron */
                $cron = Mage::getModel('sli_search/cron');
                $cron->generateFeeds();

                return true;
            }
        } catch (SLI_Search_Exception $e) {
            echo "{$e->getMessage()}\n";

            return false;
        } catch (Exception $e) {
            Mage::logException($e);
            echo "An unknown error occurred. Please contact your SLI provider. {$e->getMessage()}\n";

            return false;
        }

        echo $this->usageHelp();

        return false;
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f feed.php -- [options]

  build                     Builds Feeds For All Stores
  build --store [#]         Builds Feed For Specific Store
  cron                      Execute Feed cron

USAGE;
    }
}

// run
$sliSearchFeed = new SLI_Search_Shell_Feed();
$sliSearchFeed->run();