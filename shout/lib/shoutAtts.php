<?php
/* 
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$shoutAttributes = array();
$shoutAttributes['description'] = 'Phone System Options';
$shoutAttributes['permsNode'] = 'shout:contexts:somethingOrOther';
$shoutAttributes['attributes']['extension']['description'] = 'Internal Phone Extension';
$shoutAttributes['attributes']['extension']['type'] = 'integer';
$shoutAttributes['attributes']['extension']['size'] = 3; // max length for this particular string
$shoutAttributes['attributes']['extension']['ldapKey'] = 'voiceextensionsomethingIhavenoidea';

$shoutAttributes['attributes']['mailboxpin']['description'] = 'Mailbox PIN';
$shoutAttributes['attributes']['mailboxpin']['type'] = 'integer';
$shoutAttributes['attributes']['mailboxpin']['size'] = 4;
$shoutAttributes['attributes']['mailboxpin']['ldapKey'] = 'voicemailboxpin';

$shoutAttributes['attributes']['phonenumbers']['description'] = 'Phone Numbers';
$shoutAttributes['attributes']['phonenumbers']['type'] = 'array';
$shoutAttributes['attributes']['phonenumbers']['size'] = 1;
$shoutAttributes['attributes']['phonenumbers']['arrayType'] = 'string';
$shoutAttributes['attributes']['phonenumbers']['ldapKey'] = 'asteriskuserphonenumbers';

?>
