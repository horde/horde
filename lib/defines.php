<?php
/**
 * Shout external API interface.
 *
 * $Id$
 *
 * This file defines Shout's constants.  Any file needing to use the
 * driver or external API should require_once() this file.
 *
 * @package Shout
 */
# Branches
@define('SHOUT_ASTERISK_BRANCH', "ou=Asterisk");
@define('SHOUT_USERS_BRANCH', "ou=People");

# Object Classes - Users
@define('SHOUT_USER_OBJECTCLASS', "asteriskUser");

# Object Classes - Contexts
@define('SHOUT_CONTEXT_CUSTOMERS_OBJECTCLASS', 'vofficeCustomer');
@define('SHOUT_CONTEXT_EXTENSIONS_OBJECTCLASS', 'asteriskExtensions');
@define('SHOUT_CONTEXT_MOH_OBJECTCLASS', 'asteriskMusiconHold');
@define('SHOUT_CONTEXT_CONFERENCE_OBJECTCLASS', 'asteriskMeetMe');
@define('SHOUT_CONTEXT_VOICEMAIL_OBJECTCLASS', 'asteriskVoicemail');

# Attributes - Dialplans
@define('SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE', 'asteriskExtensionLine');
@define('SHOUT_DIALPLAN_INCLUDE_ATTRIBUTE', 'asteriskIncludeLine');
@define('SHOUT_DIALPLAN_IGNOREPAT_ATTRIBUTE', 'asteriskIgnorePat');
@define('SHOUT_DIALPLAN_BARELINE_ATTRIBUTE', 'asteriskExtensionBareLine');

# Attributes - Accounts
@define('SHOUT_ACCOUNT_ID_ATTRIBUTE', 'vofficeCustomerNumber');

# Perms Fields
@define('SHOUT_CONTEXT_ALL', 0xFFF);
@define('SHOUT_CONTEXT_NONE', 0);
@define('SHOUT_CONTEXT_CUSTOMERS', 1 << 0);
@define('SHOUT_CONTEXT_EXTENSIONS', 1 << 1);
@define('SHOUT_CONTEXT_MOH', 1 << 2);
@define('SHOUT_CONTEXT_CONFERENCE', 1 << 3);
@define('SHOUT_CONTEXT_VOICEMAIL', 1 << 4);