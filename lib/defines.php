<?php
/**
 * Shout external API interface.
 *
 * $Horde: shout/lib/defines.php,v 1.120.2.4 2005/01/24 11:01:27 ben Exp $
 *
 * This file defines Shout's constants.  Any file needing to use the
 * driver or external API should require_once() this file.
 *
 * @package Shout
 */
@define('SHOUT_ASTERISK_BRANCH', "ou=Asterisk");
@define('SHOUT_USERS_BRANCH', "ou=Customers");
@define('SHOUT_USER_OBJECTCLASS', "asteriskUser");

@define('SHOUT_CONTEXT_ALL', 0xFFF);
@define('SHOUT_CONTEXT_NONE', 0);
@define('SHOUT_CONTEXT_CUSTOMERS', 1 << 0);
@define('SHOUT_CONTEXT_EXTENSIONS', 1 << 1);
@define('SHOUT_CONTEXT_MOH', 1 << 2);
@define('SHOUT_CONTEXT_CONFERENCE', 1 << 3);
@define('SHOUT_CONTEXT_VOICEMAIL', 1 << 4);

# FIXME Maybe these should be params instead?
@define('SHOUT_CONTEXT_CUSTOMERS_OBJECTCLASS', 'vofficeCustomer');
@define('SHOUT_CONTEXT_EXTENSIONS_OBJECTCLASS', 'asteriskExtensions');
@define('SHOUT_CONTEXT_MOH_OBJECTCLASS', 'asteriskMusiconHold');
@define('SHOUT_CONTEXT_CONFERENCE_OBJECTCLASS', 'asteriskMeetMe');
@define('SHOUT_CONTEXT_VOICEMAIL_OBJECTCLASS', 'asteriskVoicemail');

@define('SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE', 'asteriskExtensionLine');
@define('SHOUT_DIALPLAN_INCLUDE_ATTRIBUTE', 'asteriskIncludeLine');
@define('SHOUT_DIALPLAN_IGNOREPAT_ATTRIBUTE', 'asteriskIgnorePat');
@define('SHOUT_DIALPLAN_BARELINE_ATTRIBUTE', 'asteriskExtensionBareLine');

# FIXME Maybe these should be params instead?
@define('SHOUT_ACCOUNT_ID_ATTRIBUTE', 'vofficeCustomerNumber');