<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

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
if (Horde_Util::getFormData('submitbutton') == _("Revert Configuration")) {
    $path = $registry->get('fileroot', $app) . '/config';
    if (@copy($path . '/conf.bak.php', $path . '/conf.php')) {
        $notification->push(_("Successfully reverted configuration. Reload to see changes."), 'horde.success');
        @unlink($path . '/conf.bak.php');
    } else {
        $notification->push(_("Could not revert configuration."), 'horde.error');
    }
} elseif ($form->validate($vars)) {
    $config = new Horde_Config($app);
    $php = $config->generatePHPConfig($vars);
    $path = $registry->get('fileroot', $app) . '/config';
    if (file_exists($path . '/conf.php')) {
        if (@copy($path . '/conf.php', $path . '/conf.bak.php')) {
            $notification->push(sprintf(_("Successfully saved the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.success');
        } else {
            $notification->push(sprintf(_("Could not save the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.warning');
        }
    }
    if ($fp = @fopen($path . '/conf.php', 'w')) {
        /* Can write, so output to file. */
        fwrite($fp, $php);
        fclose($fp);
        $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
        $registry->clearCache();
        Horde::url('admin/config/index.php', true)->redirect();
    } else {
        /* Cannot write. */
        $notification->push(sprintf(_("Could not save the configuration file %s. You can either use one of the options to save the code back on %s or copy manually the code below to %s."), Horde_Util::realPath($path . '/conf.php'), Horde::link(Horde::url('admin/config/index.php') . '#update', _("Configuration")) . _("Configuration") . '</a>', Horde_Util::realPath($path . '/conf.php')), 'horde.warning', array('content.raw'));

        /* Save to session. */
        $session['horde:config/' . $app] = $php;
    }
} elseif ($form->isSubmitted()) {
    $notification->push(_("There was an error in the configuration form. Perhaps you left out a required field."), 'horde.error');
}

/* Set up the template. */
$template = $injector->createInstance('Horde_Template');
$template->set('php', htmlspecialchars($php), true);
/* Create the link for the diff popup only if stored in session. */
$diff_link = '';
if (isset($session['horde:config/' . $app])) {
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
$form->renderActive($renderer, $vars, 'config.php', 'post');
$template->set('form', Horde::endBuffer());

echo $template->fetch(HORDE_TEMPLATES . '/admin/config/config.html');
require HORDE_TEMPLATES . '/common-footer.inc';
