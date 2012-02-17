<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$permission = 'cmdshell';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() && 
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:'.$permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

$title = _("Command Shell");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

echo '<div>';
if ($command = trim(Horde_Util::getFormData('cmd'))) {
    echo '<h1 class="header">' . _("Command") . ':</h1><br />';
    echo '<p class="text"><code>' . nl2br(htmlspecialchars($command)) . '</code></p>';

    echo '<br /><h1 class="header">' . _("Results") . ':</h1><br />';
    echo '<pre class="text">';

    $cmds = explode("\n", $command);
    foreach ($cmds as $cmd) {
        $cmd = trim($cmd);
        if (strlen($cmd)) {
            unset($results);
            flush();
            echo htmlspecialchars(shell_exec($cmd));
        }
    }

    echo '</pre><br />';
}
?>

<form action="<?php echo Horde::url('admin/cmdshell.php') ?>" method="post">
<?php Horde_Util::pformInput() ?>
<label for="cmd" class="hidden"><?php echo _("Command") ?></label>
<h1 class="header"><?php echo $title ?></h1>
<br />
<textarea class="fixed" id="cmd" name="cmd" rows="10" cols="80">
<?php if (!empty($command)) echo htmlspecialchars($command) ?></textarea>
<br />
<input type="submit" class="button" value="<?php echo _("Execute") ?>" />
<?php echo Horde_Help::link('admin', 'admin-cmdshell') ?>

</form>
</div>
<?php

require HORDE_TEMPLATES . '/common-footer.inc';
