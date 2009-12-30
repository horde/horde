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

    $contexts = $shout->getContexts();

    $perms['tree']['shout']['contexts'] = false;
    $perms['title']['shout:contexts'] = _("Contexts");

    // Run through every contact source.
    foreach ($contexts as $context => $contextInfo) {
        $perms['tree']['shout']['contexts'][$context] = false;
        $perms['title']['shout:contexts:' . $context] = $context;

        foreach(
            array(
                'users' => 'Users',
                'dialplan' => 'Dialplan',
                'moh' => 'Music on Hold',
                'conferences' => 'Conferencing',
            )
            as $module => $modname) {
            $perms['tree']['shout']['contexts'][$context][$module] = false;
            $perms['title']["shout:contexts:$context:$module"] = $modname;
        }
    }

//     function _shout_getContexts($searchfilters = SHOUT_CONTEXT_ALL,
//                          $filterperms = null)

    return $perms;
}

function _shout_attributes()
{
    // See CONGREGATION_BASE/docs/api.txt for information on the structure
    // of this array.
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
