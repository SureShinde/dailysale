<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Contact extends Bronto_Common_Helper_Data
{
    /**
     * @param string $email
     * @param string $customSource
     * @param int    $store
     *
     * @return Bronto_Api_Contact_Row
     */
    public function getContactByEmail($email, $customSource = null, $store = null)
    {
        if (empty($email)) {
            return false;
        }

        /* @var $contactObject Bronto_Api_Contact */
        $api           = $this->getApi(null, 'store', $store);
        $contactObject = $api->getContactObject();

        // Load Contact
        $contact        = $contactObject->createRow(array('email' => $email));
        $contact->email = $email;
        try {
            $contact = $contact->read();
        } catch (Exception $e) {
            // Contact doesn't exist
            $this->writeDebug('No Contact exists with email: ' . $email);
            // Set customSource if available
            if (!empty($customSource)) {
                $contact->customSource = $customSource;
            }
        }

        return $contact;
    }

    /**
     * A more efficient way to read multiple emails from Bronto
     *
     * @param array $emails
     * @param string $customSource (Optiona)
     * @param int $store (Optional)
     * @param bool $createNonExistent (Optional)
     *
     * @return array Bronto_Api_Contact_Row
     */
    public function getContactsByEmail($emails, $customSource = null, $store = null, $createNonExistent = false)
    {
        if (empty($emails)) {
            return false;
        }

        $api = $this->getApi(null, 'store', $store);
        $contactObject = $api->getContactObject();
        $filter = array(
            'type' => 'OR',
            'email' => array(),
        );
        foreach ($emails as $email) {
            $filter['email'][] = array(
                'operator' => 'EqualTo',
                'value' => $email
            );
        }

        $results = $contactObject->readAll($filter);
        if (count($results) != count($emails)) {
            $contacts = array();
            foreach ($results as $contact) {
                $contacts[$contact->email] = $contact;
            }

            $newContacts = array();
            foreach ($emails as $email) {
                if (!isset($contacts[$email])) {
                    $contact = $contactObject->createRow(array('email' => $email));
                    $contact->customSource = $customSource;
                    $newContacts[$email] = $contact;
                }
            }

            if ($createNonExistent) {
                return $contacts + $this->saveContacts($newContacts);
            } else {
                return $contacts + $newContacts;
            }
        } else {
            return $results;
        }
    }

    /**
     * @param Bronto_Api_Contact_Row $contact
     * @param bool                   $persistOnly
     *
     * @return Bronto_Api_Contact_Row
     */
    public function saveContact(Bronto_Api_Contact_Row $contact, $persistOnly = false)
    {

        if ($persistOnly) {
            $contact->persist();
        } else {
            try {
                if ($contact->id) {
                    $this->writeDebug("Updating existing Contact: ({$contact->email})...");
                } else {
                    $this->writeDebug("Saving new Contact: ({$contact->email})...");
                }
                $contact->save(false);
            } catch (Exception $e) {
                $this->writeError($e);
            }
            $this->_flushApiLogs($contact->getApi());
        }

        return $contact;
    }

    /**
     * More efficient way add saving multiple contacts
     *
     * @param array Bronto_Api_Contact_Row
     * @return array Bronto_Api_Contact_Row
     */
    public function saveContacts($contacts)
    {
        $contactObject = null;
        $lookupTable = array();
        foreach ($contacts as $index => $contact) {
            $contactObject = $contact->getApiObject();
            $this->saveContact($contact, true);
            $lookupTable[] = $index;
        }

        if ($contactObject) {
            try {
                $results = $contactObject->flush();
                foreach ($results as $index => $result) {
                    $contact = $contacts[$lookupTable[$index]];
                    if ($result->hasError()) {
                        $this->writeError("Failed to create contact {$contact->email}: ({$result->getErrorCode()}): {$result->getErrorMessage()}");
                        $contact->error = $result->getErrorMessage();
                    } else {
                        $contact->id = $result->id;
                    }
                }
            } catch (Exception $e) {
                $this->writeError($e);
            }
            $this->_flushApiLogs($contactObject->getApi());
        }
        return $contacts;
    }

    /**
     * Writes the contact save logs
     *
     * @param Bronto_Api $api
     * @return void
     */
    protected function _flushApiLogs($api)
    {
        $this->writeVerboseDebug('===== CONTACT SAVE =====', 'bronto_common_api.log');
        $this->writeVerboseDebug(var_export($api->getLastRequest(), true), 'bronto_common_api.log');
        $this->writeVerboseDebug(var_export($api->getLastResponse(), true), 'bronto_common_api.log');
    }
}
