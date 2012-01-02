<?php
/**
 * Horde_Imsp_Book Class - provides api for dealing with IMSP
 * address books.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp_Book
{
    /**
     * Supported ACLs
     *
     */
    const ACL_RIGHTS = 'lrwcda';

    /**
     * Sort order.
     *
     * @var string
     */
    public $sort = 'ascend';

    /**
     * Horde_Imsp_Client object.
     *
     * @var Horde_Imsp_Client_Base
     */
    protected $_imsp;

    /**
     * Parameter list.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor function.
     *
     * @param array $params Hash containing IMSP parameters.
     */
    public function __construct(Horde_Imsp_Client_Base $client, array $params)
    {
        $this->_params = $params;
        $this->_imsp = $client;
    }

    /**
     * Returns an array containing the names of all the address books
     * available to the logged in user.
     *
     * @return mixed Array of address book names
     */
    public function getAddressBookList()
    {
        $command_string = 'ADDRESSBOOK *';

        $this->_imsp->send($command_string);

        /* Iterate through the response and populate an array of
         * address book names. */
        $server_response = $this->_imsp->receive();
        $abooks = array();
        while (preg_match("/^\* ADDRESSBOOK/", $server_response)) {
            /* If this is an ADDRESSBOOK response, then this will explode as so:
             * [0] and [1] can be discarded
             * [2] = attributes
             * [3] = delimiter
             * [4] = address book name
             */

            /* First, check for a {} */
            if (preg_match(Horde_Imsp_Client_Socket::OCTET_COUNT, $server_response, $tempArray)) {
                $abooks[] = $this->_imsp->receiveStringLiteral($tempArray[2]);
                /* Get the CRLF at end of ADDRESSBOOK response
                 * that the {} does not include. */
                $this->_imsp->receiveStringLiteral(2);
            } else {
                $parts = explode(' ', $server_response);
                $numParts = count($parts);
                $name = $parts[4];
                $firstChar = substr($name, 0, 1);
                if ($firstChar =="\"") {
                    $name = ltrim($name, "\"");
                    for ($i = 5; $i < $numParts; $i++) {
                        $name .= ' ' . $parts[$i];
                        $lastChar = substr($parts[$i], strlen($parts[$i]) - 1, 1);
                        if ($lastChar == "\"") {
                            $name = rtrim($name, "\"");
                            break;
                        }
                    }
                }
                $abooks[] = $name;
            }
            $server_response = $this->_imsp->receive();
        }

        if ($server_response != 'OK') {
            $this->_imsp->_logger->err('Did not receive expected response frm server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
        $this->_imsp->_logger->debug('ADDRESSBOOK command OK.');

        return $abooks;
    }

    /**
     * Returns an array containing the names that match $search
     * critera in the address book named $abook.
     *
     * @param string $abook  Address book name to search.
     * @param mixed $search  Search criteria either a string (name) or an array
     *                       in the form of 'fieldName' => 'searchTerm'.
     *
     * @return array Array of names of the entries that match.
     * @throws Horde_Imsp_Exception
     */
    public function search($abook, $search)
    {
        //If no field => value pairs, assume we are searching name.
        $criteria = array();
        if (!is_array($search)) {
            $criteria['name'] = $search;
        } else {
            $criteria = $search;
        }

        $this->_imsp->send('SEARCHADDRESS ', true, false);

        // Do we need to send the abook name as {} ?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }

        //Start parsing the search array.
        $this->_imsp->send("$abook", false, false);
        $count = count($criteria);
        $current = 1;
        foreach ($criteria as $search_field => $search) {
            $this->_imsp->send(" $search_field ", false, false);
            // How about the search term as a {}.
            if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $search)) {
                $biSearch = sprintf("{%d}", strlen($search));
                $this->_imsp->send($biSearch, false, true, true);
                $this->_imsp->send($search, false, $current == $count);
                $current++;
            } else {
                // Only send the CrLf if this is the last field/search atom.
                $this->_imsp->send('"' . $search . '"', false, $current == $count);
                $current++;
            }
        }

        // Get the response.
        $server_response = $this->_imsp->receive();
        $abookNames = Array();

        while (preg_match("/^\* SEARCHADDRESS/", $server_response)) {
            $chopped_response = preg_replace("/^\* SEARCHADDRESS/", '', $server_response);

            // Remove any lingering white space in front only.
            $chopped_response = ltrim($chopped_response);

            // Get rid of any lingering quotes.
            $temp = preg_replace("/\"/", '', $chopped_response);

            if (preg_match("/({)([0-9]{1,})(\}$)/", $temp, $tempArray)) {
                $dataSize = $tempArray[2];
                $temp = $this->_imsp->receiveStringLiteral($dataSize);

                /* Get the CRLF since {} does not include it. */
                $this->_imsp->receiveStringLiteral(2);
            }

            $abookNames[] = $temp;

            // Get the next response line from the server.
            $server_response = $this->_imsp->receive();
        }

        // Should check for OK or BAD here just to be certain.
        switch ($server_response) {
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request:' . $command_text);
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request: ' . $command_text);
        case 'NO':
            $this->_imsp->_logger->err('IMSP server is unable to perform your request: ' . $this->_imsp->lastRawError);
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request: ' . $this->_imsp->lastRawError);
        }

        /* This allows for no results */
        if (count($abookNames) < 1) {
            return $abookNames;
        }

        $this->_imsp->_logger->debug('SEARCHADDRESS command OK');

        // Determine the sort direction and perform the sort.
        switch ($this->sort) {
        case 'ascend':
            sort($abookNames);
            break;

        case 'descend':
            rsort($abookNames);
            break;
        }

        return $abookNames;
    }

    /**
     * Returns an associative array of a single address book entry.
     * Note that there will always be a 'name' field.
     *
     * @param string $abook       Name of the address book to search.
     * @param string $entryName  'name' attribute of the entry to retrieve
     *
     * @return array  Array containing entry.
     * @throws Horde_Imsp_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEntry($abook, $entryName)
    {
        $this->_imsp->send('FETCHADDRESS ', true, false);
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $entryName)) {
            $biName = sprintf("{%d}", strlen($entryName));
            $this->_imsp->send($biName, false, true, true);
            $this->_imsp->send($entryName, false, true);
        } else {
            $this->_imsp->send("\"$entryName\"", false, true);
        }

        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request');
        case 'NO':
            throw new Horde_Exception_NotFound('No entry in this address book matches your query.');
        }

        // Get the data in an associative array.
        $entry = $this->_parseFetchAddressResponse($server_response);

        //Get the next server response -- this *should* be the OK response.
        $server_response = $this->_imsp->receive();
        if ($server_response != 'OK') {
            // Unexpected response throw error but still continue on.
            $this->_imsp->_logger->err('Did not receive the expected response from the server.');
        }
        $this->_imsp->_logger->debug('FETCHADDRESS completed OK');

        return $entry;
    }

    /**
     * Creates a new address book.
     *
     * @param string $abookName FULLY QUALIFIED name such 'jdoe.clients' etc...
     *
     * @throws Horde_Imsp_Exception
     */
    public function createAddressBook($abookName)
    {
        $command_text = 'CREATEADDRESSBOOK ';

        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abookName)) {
            $biBook = sprintf("{%d}", strlen($abookName));
            $this->_imsp->send($command_text . $biBook, true, true, true);
            $this->_imsp->send($abookName, false, true);
        } else {
            $this->_imsp->send($command_text . $abookName, true, true);
        }

        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'OK':
            $this->_imsp->_logger->debug('CREATEADDRESSBOOK completed OK');
            break;
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        default:
            // Something unexpected.
            $this->_imsp->_logger->err('Did not receive the expected response from the server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
    }

    /**
     * Deletes an address book completely!
     *
     * @param string $abookName Name of address book to delete.
     *
     * @throws Horde_Imsp_Exception
     */
    public function deleteAddressBook($abookName)
    {
        $command_text = 'DELETEADDRESSBOOK ';

        // Check need for {}.
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abookName)) {
            $biBook = sprintf("{%d}", strlen($abookName));
            $this->_imsp->send($command_text . $biBook, true, true, true);
            $this->_imsp->send($abookName, false, true);
        } else {
            $this->_imsp->send($command_text . $abookName, true, true);
        }
        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'OK':
            $this->_imsp->_logger->debug('DELETEADDRESSBOOK completed OK');
            break;
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        default:
            // Something unexpected.
            $this->_imsp->_logger->err('Did not receive the expected response from the server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
    }

    /**
     * Renames an address book.
     *
     * @param string $abookOldName Old name.
     * @param string $abookNewName New address book name.
     *
     * @throws Horde_Imsp_Exception
     */
    public function renameAddressBook($abookOldName, $abookNewName)
    {
        $this->_imsp->send('RENAMEADDRESSBOOK ', true, false);
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abookOldName)) {
            $biOldName = sprintf("{%d}", strlen($abookOldName));
            $this->_imsp->send($biOldName, false, true);
            $this->_imsp->receive();
        }

        $this->_imsp->send("$abookOldName ", false, false);
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abookNewName)) {
            $biNewName = sprintf("{%d}", strlen($abookNewName));
            $this->_imsp->send($biNewName, false, true);
            $this->_imsp->receive();
        }
        $this->_imsp->send($abookNewName, false, true);

        // Get server response.
        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        case 'OK':
            $this->_imsp->_logger->debug("Address book $abookOldName successfully changed to $abookNewName");
            break;
        default:
            // Something unexpected.
            $this->_imsp->_logger->err('Did not receive the expected response from the server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
    }

    /**
     * Adds an address book entry to an address book.
     *
     * @param string $abook     Name of address book to add entry to.
     * @param array $entryInfo  Address book entry information -
     *                          there MUST be a field 'name' containing the
     *                          entry name.
     *
     * @throws Horde_Imsp_Exception
     */
    public function addEntry($abook, array $entryInfo)
    {
        $command_text = '';

        // Lock the entry if it already exists.
        $this->lockEntry($abook, $entryInfo['name']);
        $this->_imsp->send('STOREADDRESS ', true, false);

        // Take care of the name.
        $entryName = $entryInfo['name'];

        // {} for book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true);
            $this->_imsp->receive();
        }
        $this->_imsp->send("$abook ", false, false);

        // Do we need {} for entry name as well?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $entryName)) {
            $biname = sprintf("{%d}", strlen($entryName));
            $this->_imsp->send($biname, false, true);
            $this->_imsp->receive();
            $this->_imsp->send($entryName, false, false);
        } else {
            $this->_imsp->send("\"$entryName\" ", false, false);
        }

        while (list($key, $value) = each($entryInfo)) {
            // Do not sent the key name 'name'.
            if ($key != 'name') {
                // Protect from extraneous white space
                $value = trim($value);

                // For some reason, tabs seem to break this.
                $value = preg_replace("/\t/", "\n\r", $value);

                // Check to see if we need {}
                if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $value)) {
                    $command_text .= $key . sprintf(" {%d}", strlen($value));
                    $this->_imsp->send($command_text, false, true);
                    $server_response = $this->_imsp->receive();
                    $command_text = '';
                    if (!preg_match("/^\+/",  $server_response)) {
                        $this->_imsp->_logger->err('Did not receive the expected response from the server.');
                        throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
                    }
                    $this->_imsp->send($value, false, false);
                } else {
                    // If we are here, then we do not need to send a literal.
                    $value = "\"" . $value . "\"";
                    $command_text .= $key . ' ' . $value . ' ';
                }
            }
        }

        // Send anything that is left of the command.
        $this->_imsp->send($command_text, false, true);
        $server_response = $this->_imsp->receive();

        switch ($server_response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        }

        if ($server_response != 'OK') {
            // Cyrus-IMSP server sends a FETCHADDRESS Response here.
            // Do others?     This was not in the RFC.
            $dummy_array = $this->_parseFetchAddressResponse($server_response);
            $server_response = $this->_imsp->receive();
            switch ($server_response) {
            case 'NO':
                // Could not create abook.
                $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
                throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
            case 'BAD':
                $this->_imsp->_logger->err('The IMSP server did not understand your request.');
                throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
            case 'OK':
                $this->_imsp->_logger->debug('STOREADDRESS Completed successfully.');

                //we were successful...so release the lock on the entry
                $this->unlockEntry($abook, $entryInfo['name']);
            }
        }
    }

    /**
     * Deletes an abook entry.
     *
     * @param string $abook     Name of address book containing entry.
     * @param string $bookEntry Name of entry to delete.
     *
     * @throws Horde_Imsp_Exception
     */
    public function deleteEntry($abook, $bookEntry)
    {
        // Start the command.
        $this->_imsp->send('DELETEADDRESS ', true, false);
        // Need {} for book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);

        //How bout for the entry name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $bookEntry)) {
            $biEntry = sprintf("{%d}", strlen($bookEntry));
            $this->_imsp->send($biEntry, false, true, true);
        } else {
            $bookEntry = $this->_imsp->quoteSpacedString($bookEntry);
        }
        $this->_imsp->send($bookEntry, false, true);
        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        case 'OK':
            $this->_imsp->_logger->debug('DELETE Completed successfully.');
        }
    }

    /**
     * Attempts to acquire a semaphore on the address book entry.
     *
     * @param string $abook     Address book name
     * @param string $bookEntry Name of entry to lock
     *
     * @return mixed true or array on success (depends on server in use).
     */
    public function lockEntry($abook, $bookEntry)
    {
        $this->_imsp->send('LOCK ADDRESSBOOK ', true, false);

        // Do we need a string literal?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);
        // What about the entry name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $bookEntry)) {
            $biEntry = sprintf("{%d}", strlen($bookEntry));
            $this->_imsp->send($biEntry, false, true, true);
            $this->_imsp->send($bookEntry, false, true);
        } else {
            $bookEntry = $this->_imsp->quoteSpacedString($bookEntry);
            $this->_imsp->send("$bookEntry", false, true);
        }

        $server_response = $this->_imsp->receive();
        do {
            switch ($server_response) {
            case 'NO':
                // Could not create abook.
                $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
                throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
            case 'BAD':
                $this->_imsp->_logger->err('The IMSP server did not understand your request.');
                throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
            }

            //Check to see if this is a FETCHADDRESS resonse
            $dummy = $this->_parseFetchAddressResponse($server_response);
            if ($dummy) {
                $server_response = $this->_imsp->receive();
            }
        } while ($server_response != 'OK');

        $this->_imsp->_logger->debug("LOCK ADDRESSBOOK on $abook $bookEntry OK");

        // Return either true or the FETCHADDRESS response if it exists.
        if (!$dummy) {
            return true;
        } else {
            return $dummy;
        }
    }

    /**
     * Unlocks a previously locked address book.
     *
     * @param string $abook     Name of address book containing locked entry.
     * @param string $bookEntry Name of entry to unlock.
     *
     * @throws Horde_Imsp_Exception
     */
    public function unlockEntry($abook, $bookEntry)
    {
        // Start sending command.
        $this->_imsp->send('UNLOCK ADDRESSBOOK ', true, false);

        // {} for book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);
        //How bout for entry name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $bookEntry)) {
            $biEntry=sprintf("{%d}", strlen($bookEntry));
            $this->_imsp->send($biEntry, false, true, true);
            $this->_imsp->send($bookEntry, false, true);
        } else {
            $bookEntry = $this->_imsp->quoteSpacedString($bookEntry);
            $this->_imsp->send("$bookEntry", false, true);
        }
        $response = $this->_imsp->receive();
        switch ($response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        case 'OK':
            $this->_imsp->_logger->debug("UNLOCK ADDRESSBOOK on $abook $bookEntry OK");
        }
    }

    /**
     * Access Control List (ACL)  Methods.
     *
     * The following characters are recognized ACL characters: lrwcda
     * l - "lookup"  (see the name and existence of the address book)
     * r - "read"    (search and retrieve addresses from address book)
     * w - "write"   (create/edit new address book entries - not delete)
     * c - "create"  (create new address books under the current address book)
     * d - "delete"  (delete entries or entire book)
     * a - "admin"   (set ACL lists for this address book - usually only
     *               allowed for the owner of the address book)
     *
     * examples:
     *  "lr" would be read only for that user
     *  "lrw" would be read/write
     */

    /**
     * Sets an Access Control List for an abook.
     *
     * @param string $abook Name of address book.
     * @param string $ident Name of user for this acl.
     * @param string $acl   acl for this user/book.
     *
     * @return mixed True on success / PEAR_Error on failure.
     */
    public function setACL($abook, $ident, $acl)
    {
        // Verify that $acl looks good.
        if (preg_match("/[^" . self::ACL_RIGHTS . "]/", $acl)) {
            $this->_imsp->_logger('Bad Argument');
            throw new InvalidArgumentException();
        }

        // Begin sending command.
        $this->_imsp->send('SETACL ADDRESSBOOK ', true, false);
        // {} for book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);

        // {} for ident?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $ident)) {
            $biIdent = sprintf("{%d}", strlen($ident));
            $this->_imsp->send($biIdent, false, true, true);
        }
        $this->_imsp->send("$ident ", false, false);

        // Now finish up with the actual ACL.
        $this->_imsp->send($acl, false, true);
        $response = $this->_imsp->receive();
        switch ($response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        case 'OK':
            $this->_imsp->_logger->debug("ACL set for $ident on $abook");
            break;
        default:
            // Do not know why we would make it down here.
            $this->_imsp->_logger->err('Did not receive the expected response from the server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
    }

    /**
     * Retrieves an address book's ACL.
     *
     * @param string $abook Name of address book to retrieve acl for.
     *
     * @return mixed array containing acl for every user with access to
     *                     address book or PEAR_Error on failure.
     */
    public function getACL($abook)
    {
        $this->_imsp->send('GETACL ADDRESSBOOK ', true, false);

        // {} for book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biName = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biName, false, true, true);
        }
        $this->_imsp->send($abook, false, true);

        // Get results.
        $response = $this->_imsp->receive();
        switch ($response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        }

        // If we are here, we need to receive the * ACL Responses.
        do {
            /* Get an array of responses.
             * The [3] element should be the address book name
             * [4] and [5] will be user/group name and permissions */

            //the book name might be a literal
            if (preg_match(Horde_Imsp_Client_Base::OCTET_COUNT, $response, $tempArray)) {
                $data = $this->_imsp->receiveStringLiteral($tempArray[2]);
                $response = $this->_imsp->receive();
            }

            $parts = explode(' ', $response);

            // Push the array if book was a literal
            if ($data) {
                array_unshift($parts, ' ', ' ', ' ', ' ');
            }
            // Address book name quoted?
            $numParts = count($parts);
            $name = $parts[3];
            $firstACLIdx = 4;
            $firstChar = substr($name, 0, 1);
            if ($firstChar == "\"") {
                for ($i = 4; $i < $numParts; $i++) {
                    $lastChar = substr($parts[$i], strlen($parts[$i]) - 1, 1);
                    $firstACLIdx++;
                    if ($lastChar == "\"") {
                        break;
                    }
                }
            }

            for ($i = $firstACLIdx; $i < count($parts); $i += 2) {
                $results[$parts[$i]] = $parts[$i+1];
            }

            $response = $this->_imsp->receive();

        } while (preg_match("/^\* ACL ADDRESSBOOK/", $response));

        // Hopefully we can receive an OK response here
        if ($response != 'OK') {
            // Some weird problem
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
        $this->_imsp->_logger->debug("GETACL on $abook completed.");

        return $results;
    }

    /**
     * Deletes an ACL entry for an address book.
     *
     * @param string $abook Name of the address book.
     * @param string $ident Name of entry to remove acl for.
     *
     * @throws Horde_Imsp_Exception
     */
    function deleteACL($abook, $ident)
    {
        $this->_imsp->send('DELETEACL ADDRESSBOOK ', true, false);

        // Do we need literal for address book name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send("$abook ", false, false);

        // Literal for ident name?
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $ident)) {
            $biIdent = sprintf("{%d}", strlen($ident));
            $this->_imsp->send($biIdent, false, true, true);
            $this->_imsp->send($ident, false, true);
        } else {
            $this->_imsp->send("\"$ident\"", false, true);
        }

        // Get results.
        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        case 'OK':
            $this->_imsp->_logger->debug("DELETED ACL for $ident on $abook");
        default:
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
    }

    /**
     * Returns an ACL string containing the rights for the current user
     *
     * @param string $abook Name of address book to retrieve acl.
     *
     * @return mixed acl of current user.
     */
    public function myRights($abook)
    {
        $data = '';
        $this->_imsp->send('MYRIGHTS ADDRESSBOOK ', true, false);
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $abook)) {
            $biBook = sprintf("{%d}", strlen($abook));
            $this->_imsp->send($biBook, false, true, true);
        }
        $this->_imsp->send($abook, false, true);
        $server_response = $this->_imsp->receive();
        switch ($server_response) {
        case 'NO':
            // Could not create abook.
            $this->_imsp->_logger->err('IMSP server is unable to perform your request.');
            throw new Horde_Imsp_Exception('IMSP server is unable to perform your request.');
        case 'BAD':
            $this->_imsp->_logger->err('The IMSP server did not understand your request.');
            throw new Horde_Imsp_Exception('The IMSP server did not understand your request.');
        }

        if (!preg_match("/^\* MYRIGHTS ADDRESSBOOK/", $server_response)) {
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }

        // {} for the abook name?
        if (preg_match(Horde_Imsp_Client_Base::OCTET_COUNT, $server_response, $tempArray)) {
            $data = $this->_imsp->receiveStringLiteral($tempArray[2]);
            $server_response = $this->_imsp->receive();
        }

        $parts = explode(' ', $server_response);

        // Push the array if we had a {}
        if ($data) {
            array_unshift($parts, ' ', ' ', ' ', ' ');
        }

        // Quoted address book name?
        $numParts = count($parts);
        $name = $parts[3];
        $firstACLIdx = 4;
        $firstChar = substr($name, 0, 1);
        if ($firstChar == "\"") {
            for ($i = 4; $i < $numParts; $i++) {
                $lastChar = substr($parts[$i], strlen($parts[$i]) - 1, 1);
                $firstACLIdx++;
                if ($lastChar == "\"") {
                    break;
                }
            }
        }

        $acl = $parts[$firstACLIdx];
        $server_response = $this->_imsp->receive();

        if ($server_response != 'OK') {
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        } else {
            $this->_imsp->_logger->debug("MYRIGHTS on $abook completed.");
            return $acl;
        }
    }

    /**
     * Parses a IMSP fetchaddress response text string into key-value pairs
     *
     * @param  string  $server_response The raw fetchaddress response.
     *
     * @return array   Address book entry information as key=>value pairs.
     */
    protected function _parseFetchAddressResponse($server_response)
    {
        $abook = '';
        if (!preg_match("/^\* FETCHADDRESS /", $server_response)) {
            $this->_imsp->_logger->err('Did not receive a FETCHADDRESS response from server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }

        /* NOTES
         * Parse out the server response string
         *
         * After choping off the server command response tags and
         * explode()'ing the server_response string
         * the $parts array contains the chunks of the server returned data.
         *
         * The predifined 'name' field starts in $parts[1].
         * The server should return any single item of data
         * that contains spaces within it as a double quoted string.
         * So we can interpret the existence of a double quote at the beginning
         * of a chunk to mean that the next chunk(s) are part of
         * the same value.  A double quote at the end of a chunk signifies the
         * end of that value and the chunk following that can be interpreted
         * as a key name.
         *
         * We also need to watch for the server returning a {} response for the
         * value of the key as well. */

        // Was the address book name a  {}?
        if (preg_match("/(^\* FETCHADDRESS )({)([0-9]{1,})(\}$)/",
                       $server_response, $tempArray)) {
            $abook = $this->_imsp->receiveStringLiteral($tempArray[3]);
            $chopped_response = trim($this->_imsp->receive());
        } else {
            // Take off the stuff from the beginning of the response
            $chopped_response = trim(preg_replace("/^\* FETCHADDRESS /", '', $server_response));
        }

        $parts = explode(' ', $chopped_response);
        /* If addres book was sent as a {} then we must 'push' a blank
         * value to the start of this array so the rest of the routine
         * will work with the correct indexes. */
         if (!empty($abook)) {
            array_unshift($parts, ' ');
        }

        // Was the address book name quoted?
        $numOfParts = count($parts);
        $name = $parts[0];
        $firstNameIdx = 1;
        $firstChar = substr($name, 0, 1);
        if ($firstChar =="\"") {
            for ($i = 1; $i < $numOfParts; $i++) {
                $lastChar = substr($parts[$i], strlen($parts[$i]) - 1, 1);
                $firstNameIdx++;
                if ($lastChar == "\"") {
                    break;
                }
            }
        }

        // Now start working on the entry name
        $name = $parts[$firstNameIdx];
        $firstChar = substr($name,0,1);

        // Check to see if the first char of the name string is a double quote
        // so we know if we have to extract more of the name.
        if ($firstChar == "\"") {
            $name = ltrim($name, "\"");
            for ($i = $firstNameIdx + 1; $i < $numOfParts; $i++) {
                $name .=  ' ' . $parts[$i];
                $lastChar = substr($parts[$i], strlen($parts[$i]) - 1,1);
                if ($lastChar == "\"") {
                    $name = rtrim($name, "\"");
                    $nextKey = $i + 1;
                    break;
                }
            }

        // Check for {}
        } elseif (preg_match('/\{(\d+)\}/', $name, $matches)) {
            $name = $this->_imsp->receiveStringLiteral($matches[1]);
            $response=$this->_imsp->receive();
            $parts = explode(' ', $response);
            $numOfParts = count($parts);
            $nextKey = 0;
        } else {
            // If only one chunk for 'name' then we just have to point
            // to the next chunk in the array...which will hopefully
            // be '2'
            $nextKey = $firstNameIdx + 1;
        }

        $lastChar = '';
        $entry['name'] = $name;

        // Start parsing the rest of the response.
        for ($i = $nextKey; $i < $numOfParts; $i += 2) {
            $key = $parts[$i];
            /* Check for {} */
            if (@preg_match(Horde_Imsp_Client_Base::OCTET_COUNT, $parts[$i+1], $tempArray)) {
                $server_data = $this->_imsp->receiveStringLiteral($tempArray[2]);
                $entry[$key] = $server_data;

                /* Read any remaining data from the stream and reset
                 * the counter variables so the loop will continue
                 * correctly. Note we set $i  to -2 because it will
                 * be incremented by 2 before the loop will run again */
                $parts = $this->_imsp->getServerResponseChunks();
                $i = -2;
                $numOfParts = count($parts);
            } else {
                // Not a string literal response
                @$entry[$key] = $parts[$i + 1];
                 // Check to see if the value started with a double
                 // quote.  We also need to check if the last char is a
                 // quote to make sure we REALLY have to check the next
                 // elements for a closing quote.
                if ((@substr($parts[$i + 1], 0, 1) == '"') &&
                    (substr($parts[$i + 1],
                     strlen($parts[$i + 1]) - 1, 1) != '"')) {

                    do {
                        $nextElement = $parts[$i+2];

                        // Was this element the last one?
                        $lastChar = substr($nextElement, strlen($nextElement) - 1, 1);
                        $entry[$key] .= ' ' . $nextElement;

                        // NOW, we can check the lastChar.
                        if ($lastChar == '"') {
                            $done = true;
                            $i++;
                        } else {
                            // Check to see if the next element is the
                            // last one. If so, the do loop will terminate.
                            $done = false;
                            $lastChar = substr($parts[$i+3], strlen($parts[$i+3]) - 1,1);
                            $i++;
                        }
                    } while ($lastChar != '"');

                    // Do we need to add the final element, or were
                    // there only two total?
                    if (!$done) {
                        $nextElement = $parts[$i+2];
                        $entry[$key] .= ' ' . $nextElement;
                        $i++;
                    }

                    // Remove the quotes sent back to us from the server.
                    if (substr($entry[$key], 0, 1) == '"') {
                        $entry[$key] = substr($entry[$key], 1, strlen($entry[$key]) - 2);
                    }

                    if (substr($entry[$key], strlen($entry[$key]) - 1, 1) == '"') {
                        $entry[$key] = substr($entry[$key], 0, strlen($entry[$key]) - 2);
                    }
                } elseif ((@substr($parts[$i + 1], 0, 1) == '"') &&
                          (substr($parts[$i + 1], -1, 1) == '"')) {
                    // Remove the quotes sent back to us from the server.
                    if (substr($entry[$key], 0, 1) == '"') {
                        $entry[$key] = substr($entry[$key], 1, strlen($entry[$key]) - 2);
                    }

                    if (substr($entry[$key], -1, 1) == '"') {
                        $entry[$key] = substr($entry[$key], 0, strlen($entry[$key]) - 2);
                    }
                }
            }
        }

        return $entry;
    }

}
