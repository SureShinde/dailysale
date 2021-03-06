<?php
/**
 * This file was generated by the ConvertToLegacy class in bronto-legacy.
 * The purpose of the conversion was to maintain PSR-0 compliance while
 * the main development focuses on modern styles found in PSR-4.
 *
 * For the original:
 * @see src/Bronto/Api/Strategy/Standard.php
 */


/**
 * The standard error recover strategy shipped with the library
 *
 * @author Philip Cali <philip.cali@bronto.com>
 */
class Bronto_Api_Strategy_Standard implements Bronto_Api_Strategy_Error
{
    /**
     * @see parent
     * @param Bronto_Api_Exception $exception
     * @param Bronto_Api $api
     * @param Bronto_Object $request
     * @return boolean
     */
    public function recover(Bronto_Api_Exception $exception, Bronto_Api $api, Bronto_Object $request)
    {
        $canRetry = $exception->getAttempts() < $api->getOptions()->getRetries();
        if ($exception->isRecoverable() && $canRetry) {
            if ($exception->isInvalidSession()) {
                $api->login();
                return true;
            } else {
                if ($exception->isNetworkRelated() && !$request->hasUpdates()) {
                    // Incrementally backoff the read request
                    $backOff = $api->getOptions()->getBackOff() * $exception->getAttempts();
                    sleep($backOff);
                    return true;
                }
            }
        }
        return false;
    }
}
