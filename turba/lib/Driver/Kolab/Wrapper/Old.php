<?php
/**
 * Horde Turba driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Kolab_Wrapper_Old extends Turba_Driver_Kolab_Wrapper
{
    protected function _buildContact()
    {
        $k = &$this->_kolab;

        $contact = array(
            'uid' => $k->getUID(),
            'owner' => $GLOBALS['registry']->getAuth(),
            'job-title' => $k->getStr('job-title'),
            'organization' => $k->getStr('organization'),
            'body' => $k->getStr('body'),
            'web-page' => $k->getStr('web-page'),
            'nick-name' => $k->getStr('nick-name'),
        );

        $name = &$k->getRootElem('name');
        $contact['full-name'] = $k->getElemStr($name, 'full-name');
        $contact['given-name'] = $k->getElemStr($name, 'given-name');
        $contact['last-name'] = $k->getElemStr($name, 'last-name');

        $email = &$k->getRootElem('email');
        $contact['smtp-address'] = $k->getElemStr($email, 'smtp-address');

        $phones = &$k->getAllRootElems('phone');
        for ($i = 0, $j = count($phones); $i < $j; $i++) {
            $phone = &$phones[$i];
            $type = $k->getElemStr($phone, 'type');

            switch ($type) {
            case 'home1':
                $contact['home1'] = $k->getElemStr($phone, 'number');
                break;

            case 'business1':
                $contact['business1'] = $k->getElemStr($phone, 'number');
                break;

            case 'mobile':
                $contact['mobile'] = $k->getElemStr($phone, 'number');
                break;

            case 'businessfax':
                $contact['businessfax'] = $k->getElemStr($phone, 'number');
                break;
            }
        }

        $addresses = &$k->getAllRootElems('address');
        for ($i = 0, $j = count($addresses); $i < $j; $i++) {
            $address = &$addresses[$i];
            $type = $k->getElemStr($address, 'type');

            switch ($type) {
            case 'home':
                $contact['home-street'] = $k->getElemStr($address, 'street');
                $contact['home-locality'] = $k->getElemStr($address, 'locality');
                $contact['home-region'] = $k->getElemStr($address, 'region');
                $contact['home-postal-code'] = $k->getElemStr($address, 'postal-code');
                $contact['home-country'] = $k->getElemStr($address, 'country');
                break;

            case 'business':
                $contact['business-street'] = $k->getElemStr($address, 'street');
                $contact['business-locality'] = $k->getElemStr($address, 'locality');
                $contact['business-region'] = $k->getElemStr($address, 'region');
                $contact['business-postal-code'] = $k->getElemStr($address, 'postal-code');
                $contact['business-country'] = $k->getElemStr($address, 'country');
                break;
            }
        }

        return $contact;
    }

    protected function _setPhone($type, &$phone, $attributes)
    {
        if (empty($attributes[$type])) {
            $this->_kolab->delRootElem($phone);
        } else {
            if ($phone === false) {
                $phone = &$this->_kolab->appendRootElem('phone');
                $this->_kolab->setElemStr($phone, 'type', $type);
            }
            $this->_kolab->setElemStr($phone, 'number', $attributes[$type]);
        }
    }

    protected function _setAddress($type, &$address, $attributes)
    {
        if (empty($attributes["$type-street"]) && empty($attributes["$type-locality"]) &&
            empty($attributes["$type-region"]) && empty($attributes["$type-postal-code"]) &&
            empty($attributes["$type-country"])) {
            $this->_kolab->delRootElem($address);
        } else {
            if ($address === false) {
                $address = &$this->_kolab->appendRootElem('address');
                $this->_kolab->setElemStr($address, 'type', $type);
            }
            $this->_kolab->setElemStr($address, 'street', $attributes["$type-street"]);
            $this->_kolab->setElemStr($address, 'locality', $attributes["$type-locality"]);
            $this->_kolab->setElemStr($address, 'region', $attributes["$type-region"]);
            $this->_kolab->setElemStr($address, 'postal-code', $attributes["$type-postal-code"]);
            $this->_kolab->setElemStr($address, 'country', $attributes["$type-country"]);
        }
    }

    protected function _createContact(&$xml, $attributes)
    {
        $k = &$this->_kolab;

        $name = &$k->initRootElem('name');
        if (!empty($attributes['full-name'])) {
            $k->setElemStr($name, 'full-name', $attributes['full-name']);
        }
        if (!empty($attributes['given-name'])) {
            $k->setElemStr($name, 'given-name', $attributes['given-name']);
        }
        if (!empty($attributes['last-name'])) {
            $k->setElemStr($name, 'last-name', $attributes['last-name']);
        }

        $email = &$k->initRootElem('email');
        $k->setElemStr($email, 'display-name', $attributes['full-name']);
        $k->setElemStr($email, 'smtp-address', $attributes['smtp-address']);

        if (!empty($attributes['job-title'])) {
            $k->setStr('job-title', $attributes['job-title']);
        }
        if (!empty($attributes['organization'])) {
            $k->setStr('organization', $attributes['organization']);
        }
        if (!empty($attributes['body'])) {
            $k->setStr('body', $attributes['body']);
        }
        if (!empty($attributes['web-page'])) {
            $k->setStr('web-page', $attributes['web-page']);
        }
        if (!empty($attributes['nick-name'])) {
            $k->setStr('nick-name', $attributes['nick-name']);
        }

        // Phones
        $phones = &$k->getAllRootElems('phone');
        $home = false;
        $bus = false;
        $mob = false;
        $fax = false;
        for ($i = 0, $j = count($phones); $i < $j; $i++) {
            $phone = &$phones[$i];
            $type = $k->getElemStr($phone, 'type');

            switch ($type) {
            case 'home1':
                $home = &$phone;
                break;

            case 'business1':
                $bus = &$phone;
                break;

            case 'mobile':
                $mob = &$phone;
                break;

            case 'businessfax':
                $fax = &$phone;
                break;
            }
        }

        $this->_setPhone('home1', $home, $attributes);
        $this->_setPhone('business1', $bus, $attributes);
        $this->_setPhone('mobile', $mob, $attributes);
        $this->_setPhone('businessfax', $fax, $attributes);

        // Addresses
        $home = false;
        $bus = false;
        $addresses = &$k->getAllRootElems('address');
        for ($i = 0, $j = count($addresses); $i < $j; $i++) {
            $address = &$addresses[$i];
            $type = $k->getElemStr($address, 'type');

            switch ($type) {
            case 'home':
                $home = &$address;
                break;

            case 'business':
                $bus = &$address;
                break;
            }
        }

        $this->_setAddress('home', $home, $attributes);
        $this->_setAddress('business', $bus, $attributes);
    }

    /**
     * Searches the Kolab message store with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty
     * array, all records will be returned.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search($criteria, $fields)
    {
        $this->connect();

        $results = array();
        $folders = $this->_kolab->listFolders();
        foreach ($folders as $folder) {
            if ($folder[1] != 'contact') {
                continue;
            }

            $msg_list = $this->_kolab->listObjectsInFolder($folder[0]);
            if ($msg_list instanceof PEAR_Error) {
                throw new Turba_Exception($msg_list);
            } elseif (empty($msg_list)) {
                return $msg_list;
            }

            foreach ($msg_list as $msg) {
                $result = $this->_kolab->loadObject($msg, true);
                if ($result instanceof PEAR_Error) {
                    throw new Turba_Exception($result);
                }

                $contact = $this->_buildContact();

                if ($this->_matchCriteria($contact, $criteria) == false) {
                    continue;
                }

                $card = array();
                foreach ($fields as $field) {
                    $card[$field] = (isset($contact[$field]) ? $contact[$field] : '');
                }

                $results[] = $card;
            }
        }

        return $results;
    }

    /**
     * Read the given data from the Kolab message store and returns the
     * result's fields.
     *
     * @param array $criteria  Search criteria.
     * @param mixed $id_list   Data identifier.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($criteria, $id_list, $fields)
    {
        $this->connect();

        if ($criteria != 'uid') {
            return array();
        }

        if (!is_array($id_list)) {
            $id_list = array($id_list);
        }

        $results = array();
        foreach ($id_list as $id) {
            $result = $this->_kolab->loadObject($id);
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }

            $contact = $this->_buildContact($result);
            $card = array();
            foreach ($fields as $field) {
                $card[$field] = isset($contact[$field])
                    ? $contact[$field]
                    : '';
            }

            $results[] = $card;
        }

        return $results;
    }

    /**
     * Adds the specified object to the Kolab message store.
     *
     * TODO
     *
     * @throws Turba_Exception
     */
    protected function _add($attributes)
    {
        $this->connect();

        $xml = $this->_kolab->newObject($attributes['uid']);
        if ($xml instanceof PEAR_Error) {
            throw new Turba_Exception($xml);
        }

        $this->_createContact($xml, $attributes);

        $res = $this->_kolab->saveObject();
        if ($res instanceof PEAR_Error) {
            throw new Turba_Exception($res);
        }
    }

    /**
     * Removes the specified object from the Kolab message store.
     *
     * TODO
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        $this->connect();

        if ($object_key == 'uid') {
            $res = $this->_kolab->removeObjects($object_id);
            if ($res instanceof PEAR_Error) {
                throw new Turba_Exception($res);
            }
        }
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @throws Turba_Exception
     */
    protected function _deleteAll($sourceName = null)
    {
        $this->connect();

        if ($sourceName != null) {
            Horde::logMessage('deleteAll only working for current share. Called for $sourceName', 'ERR');
            throw new Turba_Exception(sprintf(_("Cannot delete all address book entries for %s"), $sourceName));
        }

        $res = $this->_kolab->removeAllObjects();
        if ($res instanceof PEAR_Error) {
            throw new Turba_Exception($res);
        }
    }

    /**
     * Updates an existing object in the Kolab message store.
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _save($object_key, $object_id, $attributes)
    {
        $this->connect();

        if ($object_key != 'uid') {
            throw new Turba_Exception('key must be uid');
        }

        $xml = $this->_kolab->loadObject($object_id);
        if ($xml instanceof PEAR_Error) {
            throw new Turba_Exception($xml);
        }

        $this->_createContact($xml, $attributes);

        $result = $this->_kolab->saveObject();
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        return $object_id;
    }

    /**
     * Checks whether a contact matches a given criteria.
     *
     * @param array $contact       The contact.
     * @param array $criteria      The criteria.
     *
     * @return boolean  Wether the passed string corresponding to $criteria.
     */
    protected function _matchCriteria($contact, $criteria)
    {
        $values = array_values($criteria);
        $values = $values[0];
        $ok = true;

        for ($current = 0; $current < count($values); ++$current) {
            $temp = $values[$current];

            while (!empty($temp) && !array_key_exists('field', $temp)) {
                $temp = array_values($temp);
                $temp = $temp[0];
            }

            if (empty($temp)) {
                continue;
            }

            $searchkey = $temp['field'];
            $searchval = $temp['test'];

            $ok = (stristr($contact[$searchkey], $searchval) == false)
                ? $ok && false
                : $ok && true;
        }

        return $ok;
    }

}
