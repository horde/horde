<?php
/**
 * Handling groups.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * Handling groups.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Integration_GroupHandlingTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Test listing groups if there are no groups.
     *
     * @scenario
     *
     * @return NULL
     */
    public function listingGroupsOnEmptyServer()
    {
        $this->given('several Kolab servers')
            ->when(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then('the list is an empty array');
    }

    /**
     * Test listing groups after adding some groups.
     *
     * @param array $group_list The groups to add.
     *
     * @scenario
     * @dataProvider groupLists
     *
     * @return NULL
     */
    public function listingGroups($group_list)
    {
        $this->given('several Kolab servers')
            ->when('adding an object list', $group_list)
            ->and(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then('the result indicates success.')
            ->and(
                'the list has a number of entries equal to',
                count($group_list)
            );
    }

    /**
     * Test the list of groups for the group id.
     *
     * @param array $group_list The groups to add.
     *
     * @scenario
     * @dataProvider groupLists
     *
     * @return NULL
     */
    public function listingGroupsHasAttributeId($group_list)
    {
        $this->given('several Kolab servers')
            ->when('adding an object list', $group_list)
            ->and(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then(
                'the provided list and the result list match with regard to these attributes',
                'mail', 'cn', $group_list
            );
    }

    /**
     * Test the list of groups for the group mail address.
     *
     * @param array $group_list The groups to add.
     *
     * @scenario
     * @dataProvider groupLists
     *
     * @return NULL
     */
    public function listingGroupsHasAttributeMail($group_list)
    {
        $this->given('several Kolab servers')
            ->when('adding an object list', $group_list)
            ->and(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then(
                'the provided list and the result list match with regard to these attributes',
                'mail', 'mail', $group_list
            );
    }

    /**
     * Test the list of groups for the group visibility.
     *
     * @param array $group_list The groups to add.
     *
     * @scenario
     * @dataProvider groupLists
     *
     * @return NULL
     */
    public function listingGroupsHasAttributeVisibility($group_list)
    {
        $this->given('several Kolab servers')
            ->when('adding an object list', $group_list)
            ->and(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then(
                'each element in the result list has an attribute',
                'visible'
            );
    }

    /**
     * Test adding an invalid group.
     *
     * @scenario
     *
     * @return NULL
     */
    public function creatingGroupsWithoutMailAddressFails()
    {
        $this->given('several Kolab servers')
            ->when('adding a group without a mail address')
            ->then(
                'the result should indicate an error with',
                'Adding object failed: The value for "mail" is missing!'
            );
    }

    /**
     * Test adding a group without setting the visibility.
     *
     * @scenario
     *
     * @return NULL
     */
    public function creatingGroupWithoutVisibilityCreatesVisibleGroup()
    {
        $this->given('several Kolab servers')
            ->when('adding an object', $this->provideGroupWithoutMembers())
            ->and(
                'retrieving a hash list with all objects of type',
                'Horde_Kolab_Server_Object_Kolabgroupofnames'
            )
            ->then(
                'each element in the result list has an attribute set to a given value',
                'visible', true
            );
    }

    /**
     * Test modifying a group mail address.
     *
     * @scenario
     *
     * @return NULL
     */
    public function modifyingGroupMailAddressIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "test@example.org"')
            ->and('modifying the mail address to "new@example.org"')
            ->then(
                'the result should indicate an error with',
                'The group cannot be modified: Changing the mail address from "test@example.org" to "new@example.org" is not allowed!'
            );
    }

    /**
     * Test modifying a group mail address.
     *
     * @scenario
     *
     * @return NULL
     */
    public function conflictBetweenGroupMailAndUserMailIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "test@example.org"')
            ->and('adding a user "Test Test" with the mail address "test@example.org"')
            ->then(
                'the result should indicate an error with',
                'The user cannot be added: Mail address "test@example.org" is already the mail address for the group "test@example.org"!'
            );
    }

    /**
     *
     * @scenario
     *
     * @return NULL
     */
    public function conflictBetweenUserMailAndGroupMailIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "Test Test" with the mail address "test@example.org"')
            ->and('adding a group with the mail address "test@example.org"')
            ->then(
                'the result should indicate an error with',
                'The group cannot be added: Mail address "test@example.org" is already the mail address of the user "Test Test"!'
            );
    }

    /**
     * @scenario
     */
    public function conflictBetweenGroupMailAndUserAliasIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "test@example.org"')
            ->and('adding a user with the alias address "test@example.org"')
            ->then(
                'the result should indicate an error with',
                'The user cannot be added: Alias address "test@example.org" is already the mail address of the group "test@example.org"!'
            );
    }

    /**
     * @scenario
     */
    public function conflictBetweenUserAliasAndGroupMailIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "Test Test" with the alias address "test@example.org"')
            ->and('adding a group with the mail address "test@example.org"')
            ->then(
                'the result should indicate an error with',
                'The group cannot be added: Mail address "test@example.org" is already the alias address of the user "Test Test"!'
            );
    }

    /**
     *  kolab/issue890 (Assigning multiple Distribution Lists to user during creation and modification)
     *
     * @scenario
     */
    public function showGroupsWhenFetchingTheUser()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('fetching the user "test@example.org"')
            ->and('listing the groups of this user')
            ->then('the list should contain "testgroup@example.org"');
    }

    /**
     * @scenario
     */
    public function allowAddingUserToGroup()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "testgroup@example.org"')
            ->and('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('modifying group with the mail address "testgroup@example.org" to contain the member "cn=Test Test".')
            ->and('fetching the groups "group@example.org"')
            ->and('listing the members of this group')
            ->then('the list should contain "test@example.org"');
    }

    /**
     * @scenario
     */
    public function allowAddingUserToGroupWhenCreatingUser()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "testgroup@example.org"')
            ->and('adding a user "cn=Test Test" with the mail address "test@example.org" and member of "testgroup@example.org"')
            ->and('fetching the groups "group@example.org"')
            ->and('listing the members of this group')
            ->then('the list should contain "test@example.org"');
    }

    /**
     * @scenario
     */
    public function allowRemovingUserFromGroup()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('modifying group with the mail address "testgroup@example.org" to contain no members.')
            ->and('fetching the groups "group@example.org"')
            ->and('listing the members of this group')
            ->then('the list is empty');
    }

    /**
     * @scenario
     */
    public function deletingUserRemovesUserFromAllDistributionLists()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('adding a group with the mail address "testgroup2@example.org" and the member "cn=Test Test"')
            ->and('deleting user "cn=Test Test"')
            ->and('listing the members of group "testgroup@example.org"')
            ->and('listing the members of group "testgroup2@example.org"')
            ->then('the list of group "testgroup@example.org" is empty')
            ->and('the list of group "testgroup2@example.org" is empty');
    }

    /**
     * @scenario
     */
    public function modifyingUserIDDoesNotChangeGroupMembership()
    {
        $this->given('several Kolab servers')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('modifying user "cn=Test Test" to ID "cn=Test2 Test"')
            ->and('listing the members of group "testgroup@example.org"')
            ->then('the list of group "testgroup@example.org" contains "cn=Test2 Test"');
    }

    /**
     * @scenario
     */
    public function addingGroupInUndefinedDomainIsNotAllowed()
    {
        $this->given('several Kolab servers')
            ->and('the only served mail domain is "example.org"')
            ->when('adding a group with the mail address "test@doesnotexist.org"')
            ->then(
                'the result should indicate an error with',
                'The group cannot be added: Domain "doesnotexist.org" is not being handled by this server!'
            );
    }

    /**
     *   kolab/issue1368 (Webinterface allows to create email addresses with slash that cyrus cannot handle)
     *
     * @scenario
     * @dataProvider invalidMails
     */
    public function disallowInvalidMailAddresses($address)
    {
        $this->given('several Kolab servers')
            ->when('adding a group with an invalid mail address', $address)
            ->then(
                'the result should indicate an error with',
                "The group cannot be added: Address \"$address\" is not a valid mail address!"
            );
    }

    /**
     * @scenario
     */
    public function objectAttributeDescriptionsCanBeRetrieved()
    {
        $this->given('several Kolab servers')
            ->when('retrieving the supported attributes by the object type "group"')
            ->then('the result is an array of Horde attribute descriptions')
            ->and('contains the description of "members"');
    }

    /**
     * @scenario
     */
    public function removingGroupFailsIfGroupDoesNotExist()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "group@example.org"')
            ->and('deleting the group with the mail address "group@example.org"')
            ->then(
                'the result should indicate an error with',
                'The group cannot be deleted: Group "group@example.org" does not exist!'
            );
    }

    /**
     * @scenario
     */
    public function removingGroupByMailSucceeds()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "test@example.org"')
            ->and('deleting the group with mail address "test@example.org"')
            ->then('the result indicates success')
            ->and('listing all groups returns an empty list');
    }

    /**
     *      kolab/issue1189 (IMAP login fails on some specific uids)
     *
     * @scenario
     */
    public function userUidsShouldNotResembleTheLocalPartOfMailAddresses()
    {
        $this->given('several Kolab servers')
            ->when('adding a group with the mail address "test@example.org"')
            ->and('adding a user with the uid "test"')
            ->then(
                'the result should indicate an error with',
                'The user cannot be added: The uid "test" matches the local part of the mail address "test@example.org" assigned to group "test@example.org"!'
            );
    }

    /**
     * kolab/issue2207 (Make it possible to enable and disable users to be able to use the webclient.)
     *
     * @scenario
     */
    public function addedUserCanLoginIfInAllowedGroup()
    {
        $this->given('several Kolab servers')
            ->and('Horde uses the Kolab auth driver')
            ->and('only members of group "testgroup@example.org" are allowed')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org" and password "test"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the result indicates success')
            ->and('the session shows "test@example.org" as the current user');
    }

    /**
     * kolab/issue2207 (Make it possible to enable and disable users to be able to use the webclient.)
     *
     * @scenario
     */
    public function addedUserCannotLoginIfInNotInAllowedGroup()
    {
        $this->given('several Kolab servers')
            ->and('Horde uses the Kolab auth driver')
            ->and('only members of group "testgroup@example.org" are allowed')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org" and password "test"')
            ->and('adding a group with the mail address "testgroup@example.org" and no members')
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the user may not login');
    }

    /**
     * kolab/issue2207 (Make it possible to enable and disable users to be able to use the webclient.)
     *
     * @scenario
     */
    public function addedUserCanLoginIfInNotInDisallowedGroup()
    {
        $this->given('several Kolab servers')
            ->and('Horde uses the Kolab auth driver')
            ->and('members of group "testgroup@example.org" may not login')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org" and password "test"')
            ->and('adding a group with the mail address "testgroup@example.org" and no members')
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the result indicates success')
            ->and('the session shows "test@example.org" as the current user');
    }

    /**
     * kolab/issue2207 (Make it possible to enable and disable users to be able to use the webclient.)
     *
     * @scenario
     */
    public function addedUserCannotLoginIfInDisallowedGroup()
    {
        $this->given('several Kolab servers')
            ->and('Horde uses the Kolab auth driver')
            ->and('members of group "testgroup@example.org" may not login')
            ->when('adding a user "cn=Test Test" with the mail address "test@example.org" and password "test"')
            ->and('adding a group with the mail address "testgroup@example.org" and the member "cn=Test Test"')
            ->and('trying to login to Horde with "test@example.org" and passowrd "test"')
            ->then('the user may not login');
    }

}
