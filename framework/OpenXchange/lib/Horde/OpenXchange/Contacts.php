<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  OpenXchange
 */

/**
 * Horde_OpenXchange_Contacts is the interface class for the contacts storage
 * of an Open-Xchange server.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   OpenXchange
 */
class Horde_OpenXchange_Contacts extends Horde_OpenXchange_Base
{
    /**
     * The folder category.
     *
     * @var string
     */
    protected $_folderType = 'contacts';

    /**
     * Column IDs mapped to column names.
     *
     * @var array
     */
    protected $_columns = array(
        1 => 'id',
        20 => 'folder_id',
        100 => 'categories',
        223 => 'uid',
        500 => 'name',
        501 => 'firstname',
        502 => 'lastname',
        503 => 'middlenames',
        504 => 'nameSuffix',
        505 => 'namePrefix',
        506 => 'homeStreet',
        507 => 'homePostalCode',
        508 => 'homeCity',
        509 => 'homeProvince',
        510 => 'homeCountry',
        511 => 'birthday',
        512 => 'maritalStatus',
        513 => 'numberChildren',
        514 => 'role',
        515 => 'nickname',
        516 => 'spouse',
        517 => 'anniversary',
        518 => 'notes',
        519 => 'department',
        520 => 'title',
        521 => 'employeeType',
        522 => 'office',
        523 => 'workStreet',
        524 => 'userid',
        525 => 'workPostalCode',
        526 => 'workCity',
        527 => 'workProvince',
        528 => 'workCountry',
        529 => 'numberEmployees',
        530 => 'salesVolume',
        531 => 'taxId',
        532 => 'commercialRegister',
        533 => 'branches',
        534 => 'businessCategory',
        535 => 'info',
        536 => 'manager',
        537 => 'assistant',
        538 => 'otherStreet',
        539 => 'otherCity',
        540 => 'otherPostalCode',
        541 => 'otherCountry',
        542 => 'workPhone',
        543 => 'workPhone2',
        544 => 'workFax',
        545 => 'callbackPhone',
        546 => 'carPhone',
        547 => 'companyPhone',
        548 => 'homePhone',
        549 => 'homePhone2',
        550 => 'homeFax',
        551 => 'cellPhone',
        552 => 'workCellPhone',
        553 => 'phone',
        554 => 'fax',
        555 => 'email',
        556 => 'emailWork',
        557 => 'emailHome',
        558 => 'website',
        559 => 'isdnPhone',
        560 => 'pager',
        561 => 'primaryPhone',
        562 => 'radioPhone',
        563 => 'telexPhone',
        564 => 'ttytddPhone',
        565 => 'imaddress',
        566 => 'imaddress2',
        567 => 'sip',
        568 => 'assistPhone',
        569 => 'company',
        570 => 'photo',
        571 => 'userField01',
        572 => 'userField02',
        573 => 'userField03',
        574 => 'userField04',
        575 => 'userField05',
        576 => 'userField06',
        577 => 'userField07',
        578 => 'userField08',
        579 => 'userField09',
        580 => 'userField10',
        581 => 'userField11',
        582 => 'userField12',
        583 => 'userField13',
        584 => 'userField14',
        585 => 'userField15',
        586 => 'userField16',
        587 => 'userField17',
        588 => 'userField18',
        589 => 'userField19',
        590 => 'userField20',
        592 => 'members',
        601 => 'phototype',
        602 => 'distributionList',
        606 => 'photourl',
        610 => 'yomifirstname',
        611 => 'yomilastname',
        612 => 'yomicompany',
        613 => 'homeAddress',
        614 => 'workAddress',
        615 => 'otherAddress',
    );

    /**
     * Returns a list contacts.
     *
     * @param integer $folder  A folder ID. If empty, returns contacts of all
     *                         visible address books.
     *
     * @return array  List of contact hashes.
     * @throws Horde_OpenXchange_Exception.
     */
    public function listContacts($folder = null)
    {
        $this->_login();

        $data = array(
            'session' => $this->_session,
            'columns' => implode(',', array_keys($this->_columns)),
        );
        if ($folder) {
            $data['folder'] = $folder;
        }

        $response = $this->_request(
            'GET',
            'contacts',
            array('action' => 'all'),
            $data
        );

        $contacts = array();
        foreach ($response['data'] as $contact) {
            $map = array();
            foreach (array_values($this->_columns) as $key => $column) {
                $map[$column] = $contact[$key];
            }
            $contacts[] = $map;
        }

        return $contacts;
    }
}
