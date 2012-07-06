<?php
/**
 * PHP Shell.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:phpshell')
));

$apps_tmp = $registry->listApps();
$apps = array();
foreach ($apps_tmp as $app) {
    // Make sure the app is installed.
    if (!file_exists($registry->get('fileroot', $app))) {
        continue;
    }

    $apps[$app] = $registry->get('name', $app) . ' (' . $app . ')';
}
asort($apps);

$application = Horde_Util::getFormData('app', 'horde');
$command = trim(Horde_Util::getFormData('php'));

$title = _("PHP Shell");
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require HORDE_TEMPLATES . '/admin/menu.inc';

?>
<div>
<form action="<?php echo Horde::url('admin/phpshell.php') ?>" method="post">
<?php Horde_Util::pformInput() ?>

<h1 class="header"><?php echo $title ?></h1>
<br />
<label for="app"><?php echo _("Application Context: ") ?></label>
<select id="app" name="app">
<?php foreach ($apps as $app => $name): ?>
 <option value="<?php echo $app ?>"<?php if ($application == $app) echo ' selected="selected"' ?>><?php echo $name ?></option>
<?php endforeach; ?>
</select><br /><br />

<label for="php" class="hidden"><?php echo _("PHP") ?></label>
<textarea class="fixed" id="php" name="php" rows="10" cols="80">
<?php if (!empty($command)) echo htmlspecialchars($command) ?></textarea>
<br />
<input type="submit" class="button" value="<?php echo _("Execute") ?>" />
<?php echo Horde_Help::link('admin', 'admin-phpshell') ?>
</form><br />

<?php

if ($command) {
    $pushed = $registry->pushApp($application);

    $part = new Horde_Mime_Part();
    $part->setContents($command);
    $part->setType('application/x-httpd-phps');
    $part->buildMimeIds();

    $pretty = $injector->getInstance('Horde_Core_Factory_MimeViewer')->create($part)->render('inline');

    echo '<h1 class="header">' . _("PHP Code") . '</h1>' .
        $pretty[1]['data'] .
        '<br />' .
        '<h1 class="header">' . _("Results") . '</h1>' .
        '<pre class="text">';
    eval($command);
    echo '</pre>';

    if ($pushed) {
        $registry->popApp();
    }
}
?>

</div>
<?php

$page_output->footer();
