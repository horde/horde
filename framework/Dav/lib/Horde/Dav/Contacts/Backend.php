<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;
use Sabre\CardDAV\Backend;

/**
 * The address book backend wrapper.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Contacts_Backend extends Backend\AbstractBackend
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry         A registry object.
     */
    public function __construct(Horde_Registry $registry)
    {
        $this->_registry = $registry;
    }

    /**
     * Returns the list of addressbooks for a specific user.
     *
     * @param string $principalUri
     * @return array
     */
    public function getAddressBooksForUser($principalUri)
    {
        list($prefix, $user) = DAV\URLUtil::splitPath($principalUri);
        if ($prefix != 'principals') {
            throw new DAV\Exception\NotFound('Invalid principal prefix path ' . $prefix);
        }

        try {
            return $this->_registry->callAppMethod(
                $this->_contacts(),
                'davGetCollections',
                array('args' => array($user))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Updates properties for an address book.
     *
     * @param string $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) 
    {
    }

    /**
     * Creates a new address book
     *
     * @param string $principalUri
     * @param string $url Just the 'basename' of the url.
     * @param array $properties
     * @return void
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
    }

    /**
     * Deletes an entire addressbook and all its contents
     *
     * @param mixed $addressBookId
     * @return void
     */
    public function deleteAddressBook($addressBookId)
    {
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * @param mixed $addressbookId
     * @return array
     */
    public function getCards($addressbookId)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_contacts(),
                'davGetObjects',
                array('args' => array($addressbookId))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns a specfic card.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */
    public function getCard($addressBookId, $cardUri)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_contacts(),
                'davGetObject',
                array('args' => array($addressBookId, $cardUri))
            );
        } catch (Horde_Exception_NotFound $e) {
            return null;
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates a new card.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData)
    {
        $this->updateCard($addressBookId, $cardUri, $cardData);
    }

    /**
     * Updates a card.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_contacts(),
                'davPutObject',
                array('args' => array($addressBookId, $cardUri, $cardData))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Deletes a card
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_contacts(),
                'davDeleteObject',
                array('args' => array($addressBookId, $cardUri))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the name of the application providing the 'contacts' interface.
     *
     * @return string  An application name.
     * @throws Sabre\DAV\Exception if no contacts application is installed.
     */
    protected function _contacts()
    {
        $contacts = $this->_registry->hasInterface('contacts');
        if (!$contacts) {
            throw new DAV\Exception('No contacts application installed');
        }
        return $contacts;
    }
}
