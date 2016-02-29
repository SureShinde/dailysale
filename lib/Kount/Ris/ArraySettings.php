<?php
/**
 * @package Kount
 * @subpackage Ris
 */

/**
 * Configuration information for RIS requests.
 *
 * Expects to be constructed with a PHP array having the following keys:
 *  - MERCHANT_ID: Kount Mechant Id
 *  - URL: RIS server URL
 *
 *  - PEM_CERTIFICATE: Path to PEM encoded x509 public certificate
 *  - PEM_KEY_FILE: Path to PEM encoded x509 private key
 *  - PEM_PASS_PHRASE: Passphrase to decrypt x509 private key
 *  OR
 *  - API_KEY: API key used for authentication instead of a certificate.
 *
 * @package Kount
 * @subpackage Ris
 * @author Kount <custserv@kount.com>
 * @version SVN: $Id$
 * @copyright 2012 Kount, Inc. All Rights Reserved.
 */
class Kount_Ris_ArraySettings implements Kount_Ris_Settings {

  /**
   * @var array
   */
  protected $settings;

  /**
   * Constructor
   * @param array $settings Configuration settings
   */
  public function __construct ($settings) {
    $this->settings = $settings;
  }

  /**
   * Get the Merchant ID.
   * @return int Kount Merchant ID (MERC)
   */
  public function getMerchantId () {
    return $this->settings['MERCHANT_ID'];
  }

  /**
   * Get the RIS server URL.
   * @return string RIS server URL
   */
  public function getRisUrl () {
    return $this->settings['URL'];
  }

  /**
   * Get the path to the Merchant's x509 public certificate.
   * @return string Filesystem path to PEM encoded x509 certificate
   */
  public function getX509CertPath () {
    return $this->settings['PEM_CERTIFICATE'];
  }

  /**
   * Get the path to the Merchant's x509 private key.
   * @return string Filesystem path to PEM encoded x509 private key
   */
  public function getX509KeyPath () {
    return $this->settings['PEM_KEY_FILE'];
  }

  /**
   * Get the passphrase for the Merchant's x509 private key.
   * @return string Passphrase needed to decrypt PEM encoded x509 private key
   */
  public function getX509Passphrase () {
    return $this->settings['PEM_PASS_PHRASE'];
  }

  /**
   * Get the maximum number of seconds for RIS connection functions to timeout.
   * @return int Number of seconds to timeout
   */
  public function getConnectionTimeout () {
    return $this->settings['CONNECT_TIMEOUT'];
  }

  /**
   * The API key for authentication. Use this in favor of certificates, which
   * have been deprecated.
   * @return string API key
   */
  public function getApiKey () {
    return $this->settings['API_KEY'];
  }

} //end Kount_Ris_ArraySettings
