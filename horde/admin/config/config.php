<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
$permission = 'configuration';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() && 
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:'.$permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

if (!Horde_Util::extensionExists('domxml') &&
    !Horde_Util::extensionExists('dom')) {
    throw new Horde_Exception('You need the domxml or dom PHP extension to use the configuration tool.');
}

$app = Horde_Util::getFormData('app');
$appname = $registry->get('name', $app);
$title = sprintf(_("%s Configuration"), $appname);

if (empty($app) || !in_array($app, $registry->listAllApps())) {
    $notification->push(_("Invalid application."), 'horde.error');
    Horde::url('admin/config/index.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();

$form = new Horde_Config_Form($vars, $app);
$form->setButtons(sprintf(_("Generate %s Configuration"), $appname));
if (file_exists($registry->get('fileroot', $app) . '/config/conf.bak.php')) {
    $form->appendButtons(_("Revert Configuration"));
}

$php = '';
$path = $registry->get('fileroot', $app) . '/config';
$configFile = $path . '/conf.php';
if (is_link($configFile)) {
    $configFile = readlink($configFile);
}
if (Horde_Util::getFormData('submitbutton') == _("Revert Configuration")) {
    if (@copy($path . '/conf.bak.php', $configFile)) {
        $notification->push(_("Successfully reverted configuration. Reload to see changes."), 'horde.success');
        @unlink($path . '/conf.bak.php');
    } else {
        $notification->push(_("Could not revert configuration."), 'horde.error');
    }
} elseif ($form->validate($vars)) {
    // @todo: replace this section with $config->writePHPConfig() in Horde 5.
    $config = new Horde_Config($app);
    $php = $config->generatePHPConfig($vars);
    if (file_exists($configFile)) {
        if (@copy($configFile, $path . '/conf.bak.php')) {
            $notification->push(sprintf(_("Successfully saved the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.success');
        } else {
            $notification->push(sprintf(_("Could not save the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.warning');
        }
    }
    if ($fp = @fopen($configFile, 'w')) {
        /* Can write, so output to file. */
        fwrite($fp, $php);
        fclose($fp);
        $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($configFile)), 'horde.success');
        $registry->rebuild();
        Horde::url('admin/config/index.php', true)->redirect();
    } else {
        /* Cannot write. */
        $notification->push(sprintf(_("Could not save the configuration file %s. You can either use one of the options to save the code back on %s or copy manually the code below to %s."), Horde_Util::realPath($configFile), Horde::link(Horde::url('admin/config/index.php') . '#update', _("Configuration")) . _("Configuration") . '</a>', Horde_Util::realPath($configFile)), 'horde.warning', array('content.raw'));

        /* Save to session. */
        $session->set('horde', 'config/' . $app, $php);
    }
} elseif ($form->isSubmitted()) {
    $notification->push(_("There was an error in the configuration form. Perhaps you left out a required field."), 'horde.error');
}

/* Set up the template. */
$template = $injector->createInstance('Horde_Template');
$template->set('php', htmlspecialchars($php), true);
/* Create the link for the diff popup only if stored in session. */
$diff_link = '';
if ($session->exists('horde', 'config/' . $app)) {
    $url = Horde::url('admin/config/diff.php', true)->add('app', $app);
    $diff_link = Horde::link('#', '', '', '', Horde::popupJs($url, array('height' => 480, 'width' => 640, 'urlencode' => true)) . 'return false;') . _("show differences") . '</a>';
}
$template->set('diff_popup', $diff_link, true);
$template->setOption('gettext', true);

require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

/* Render the configuration form. */
$renderer = $form->getRenderer();
$renderer->setAttrColumnWidth('50%');

Horde::startBuffer();
$form->renderActive($renderer, $vars, Horde::url('admin/config/config.php'), 'post');
$template->set('form', Horde::endBuffer());

echo $template->fetch(HORDE_TEMPLATES . '/admin/config/config.html');
require HORDE_TEMPLATES . '/common-footer.inc';
