<?php
define('AUTH_HANDLER', true);
$authentication = 'none';
require_once '/lib/base.php';

/* Set up the password encryption token. */
Horde_Secret::setKey(Auth::getProvider() == 'imp' ? 'auth' : 'imp');

/* Use the first server defined in servers.php. */
// CUSTOMIZE THIS

$server_key = 'localhost';
$url = '/';
// $url = '/' . $registry->get('initial_page','horde');

?>

<!-- CUSTOMIZE THIS -->
<form action="<?php echo Horde::applicationUrl('redirect.php') ?>" method="post">
User: <input name="imapuser" type="text" size="20" /><br />
Pass: <input name="pass" type="password" size="20" /><br />
<input type="hidden" name="url" value="<?php echo $url ?>" />
<input type="submit" value="Log in" />
</form>
