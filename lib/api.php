<?php
/**
 * Shout external API interface.
 *
 * $Id$
 *
 * This file defines Shout's external API interface. Other
 * applications can interact with Shout through this API.
 *
 * @package Shout
 */
@define('SHOUT_BASE', dirname(__FILE__) . "/..");
require_once SHOUT_BASE . "/lib/defines.php";

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray',
);

$_services['attributes'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray',
);

function _shout_perms()
{
    static $perms = array();
    if (!empty($perms)) {
        return $perms;
    }

    @define('SHOUT_BASE', dirname(__FILE__) . '/..');
    require_once SHOUT_BASE . '/lib/base.php';

    $perms['tree']['shout']['superadmin'] = false;
    $perms['title']['shout:superadmin'] = _("Super Administrator");

//     $contexts = $shout->getContexts();

    $perms['tree']['shout']['contexts'] = false;
    $perms['title']['shout:contexts'] = _("Contexts");

    // Run through every contact source.
    foreach ($contexts as $context => $contextInfo) {
        $perms['tree']['shout']['contexts'][$context] = false;
        $perms['title']['shout:contexts:' . $context] = $context;
    }


//     function _shout_getContexts($searchfilters = SHOUT_CONTEXT_ALL,
//                          $filterperms = null)


}

function _shout_attributes()
{
    // Attribute Layout:
    // object type = array(
    //  description = Longer human description of the attributes this
    //                application provides for this object, NOT of the object
    //                itself
    //  attributes = array(    The attributes themselves
    //      name = short name of this attribute (used as a key for the Help
    //             system as well)
    //      description = Longer description of this attribute
    //      type = field datatype, one of the objects exported by Horde_Form
    //      size = maximum size of this data
    //      keys = array(     Mapping of this attribute to all possible backends
    //          ldap = ldapAttribute
    //          sql = table.column
    //      )
    //      limit = Maxmimum number of these attributes to store, 0 = unlimited
    //      required = boolean, true means form will not process without a value
    //      infoset = one of 'basic', 'advanced' or 'restricted'.  This is used
    //                to help keep the forms simple for non-power-users.  If
    //                'required' is true and 'infoset' is anything other than
    //                'basic' then 'default' MUST be specified
    //      'default' = the default value of the field.
    //  )
    // )
    //

    $shoutAttributes = array(
        'description' => 'Phone System User Settings',
        'attributes' => array(
            'extension' => array(
                'name' => 'Extension',
                'description' => 'Phone System Extension (doubles as Voice Mailbox Number',
                'type' => 'int',
                'size' => 3,
                'keys' => array(
                    'ldap' => 'asteriskVoiceMailbox',
                ),
                'limit' => 1,
                'required' => true,
                'infoset' => 'basic',
            ),

            'mailboxpin' => array(
                'name' => 'Mailbox PIN',
                'description' => 'Voice Mailbox PIN',
                'type' => 'int',
                'size' => 12,
                'keys' => array(
                    'ldap' => 'asteriskVoiceMailboxPIN',
                ),
                'limit' => 1,
                'required' => true,
                'infoset' => 'basic',
            ),

            'phonenumbers' => array(
                'name' => 'Telephone Numbers',
                'description' => 'Dialout phone numbers',
                'type' => 'cellphone', // WHY does Horde have cellphone but NOT
                                       // telephone or just phonenumber???
                'size' => 12,
                'keys' => array(
                    'ldap' => 'telephoneNumber',
                ),
                'limit' => 5,
                'required' => true,
                'infoset' => 'basic',
            ),

            'dialstring' => array(
                'name' => 'Dial String',
                'description' => 'Asterisk raw dial string',
                'type' => 'cellphone', // WHY does Horde have cellphone but NOT
                                       // telephone or just phonenumber???
                'size' => 12,
                'keys' => array(
                    'ldap' => 'telephoneNumber',
                ),
                'limit' => 5,
                'required' => true,
                'infoset' => 'restricted',
            ),
        ),
    );

    return $shoutAttributes;
}