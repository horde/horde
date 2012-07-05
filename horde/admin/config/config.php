<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:configuration')
));

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
    $config = new Horde_Config($app);
    if ($config->writePHPConfig($vars, $php)) {
        Horde::url('admin/config/index.php', true)->redirect();
    } else {
        $notification->push(sprintf(_("Could not save the configuration file %s. You can either use one of the options to save the code back on %s or copy manually the code below to %s."), Horde_Util::realPath($configFile), Horde::link(Horde::url('admin/config/index.php') . '#update', _("Configuration")) . _("Configuration") . '</a>', Horde_Util::realPath($configFile)), 'horde.warning', array('content.raw', 'sticky'));
        $page_output->addInlineScript('document.observe(\'Growler:linkClick\', function(e) { window.location.assign(e.memo.href); });');
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

Horde::startBuffer();
require HORDE_TEMPLATES . '/admin/menu.inc';
$menu_output = Horde::endBuffer();

/* Render the configuration form. */
$renderer = $form->getRenderer();
$renderer->setAttrColumnWidth('50%');

/* Buffer the form template */
Horde::startBuffer();
$form->renderActive($renderer, $vars, Horde::url('admin/config/config.php'), 'post');
$template->set('form', Horde::endBuffer());

/* Send headers */
$page_output->header(array(
    'title' => $title
));

/* Output page */
echo $menu_output;
echo $template->fetch(HORDE_TEMPLATES . '/admin/config/config.html');
$page_output->footer();
