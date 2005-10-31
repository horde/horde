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
@define(SHOUT_ASTERISK_BRANCH, "ou=Asterisk");
@define(SHOUT_USERS_BRANCH, "ou=Customers");
@define(SHOUT_USER_OBJECTCLASS, "asteriskUser");

@define(SHOUT_CONTEXT_ALL, 0xF);
@define(SHOUT_CONTEXT_NONE, 0);
@define(SHOUT_CONTEXT_CUSTOMERS, 1 << 0);
@define(SHOUT_CONTEXT_EXTENSIONS, 1 << 1);
@define(SHOUT_CONTEXT_MOH, 1 << 2);
@define(SHOUT_CONTEXT_CONFERENCE, 1 << 3);