<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:cmdshell')
));

$title = _("Command Shell");
$page_output->header(array(
    'title' => $title
));
require HORDE_TEMPLATES . '/admin/menu.inc';

if ($command = trim(Horde_Util::getFormData('cmd'))) {
    echo '<h1 class="header">' . _("Command") . ':</h1>';
    echo '<div class="horde-content"><code>' . nl2br(htmlspecialchars($command)) . '</code></div>';

    echo '<h1 class="header">' . _("Results") . ':</h1>';
    echo '<div class="horde-content"><pre class="text">';

    $cmds = explode("\n", $command);
    foreach ($cmds as $cmd) {
        $cmd = trim($cmd);
        if (strlen($cmd)) {
            unset($results);
            flush();
            echo htmlspecialchars(shell_exec($cmd));
        }
    }

    echo '</pre></div>';
}
?>

<form action="<?php echo Horde::url('admin/cmdshell.php') ?>" method="post">
<?php Horde_Util::pformInput() ?>
<h1 class="header"><?php echo $title ?></h1>
<div class="horde-content">
  <label for="cmd" class="hidden"><?php echo _("Command") ?></label>
  <textarea class="fixed" id="cmd" name="cmd" rows="10" cols="80"><?php if (!empty($command)) echo htmlspecialchars($command) ?></textarea>
</div>
<div class="horde-form-buttons">
  <input type="submit" class="horde-default" value="<?php echo _("Execute") ?>" />
  <?php echo Horde_Help::link('admin', 'admin-cmdshell') ?>
</div>

</form>
<?php

$page_output->footer();
