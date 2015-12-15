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

        $api      = $this->getApi(null, 'store', $store);
        $contacts = $api->transferContact();
        try {
            $contact = $contacts->read()
                ->withIncludeLists(true)
                ->where->email->equalTo($email)
                ->first();
            if (!is_null($contact)) {
                return $contact;
            }
        } catch (Exception $e) {
            $this->writeError('Failed to read contact ' . $email . ': ' . $e->getMessage());
        }
        return $contacts->createObject()
            ->withEmail($email)
            ->withCustomSource($customSource);
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
        $contactObject = $api->transferContact();
        $readContacts = $contactObject->read();
        foreach ($emails as $email) {
            $readContacts->or->email->equalTo($email);
        }
        $results = $readContacts->getIterator()->toArray();
        if (count($results) != count($emails)) {
            $contacts = array();
            foreach ($results as $contact) {
                $contacts[$contact->getEmail()] = $contact;
            }

            $newContacts = array();
            foreach ($emails as $email) {
                if (!isset($contacts[$email])) {
                    $contact = $contactObject->createObject()
                        ->withEmail($email)
                        ->withStatus('transactional')
                        ->withCustomSource($customSource);
                    $newContacts[] = $contact;
                }
            }

            if ($createNonExistent) {
                return array_merge($contacts, $this->saveContacts($contactObject, $newContacts));
            } else {
                return array_merge($contacts, $newContacts);
            }
        } else {
            return $results;
        }
    }

    /**
     * More efficient way add saving multiple contacts
     *
     * @param array Bronto_Api_Contact_Row
     * @return array Bronto_Api_Contact_Row
     */
    public function saveContacts($contactObject, $contacts)
    {
        if (empty($contacts)) {
            return $contacts;
        }
        try {
            $newContacts = array();
            foreach ($contactObject->addOrUpdate()->push($contacts) as $result) {
                $item = $result->getItem();
                $contact = $result->getOriginal();
                if ($item->getIsError()) {
                    $this->writeError("Failed to create contact {$contact->getEmail()}: ({$item->getErrorCode()}): {$item->getErrorString()}");
                    $contact->withError($item->getErrorMessage());
                } else {
                    $contact->withId($item->getId());
                }
                $newContacts[] = $contact;
            }
            return $newContacts;
        } catch (Exception $e) {
            $this->writeError($e);
        }
        $this->_flushApiLogs($contactObject->getApi());
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
