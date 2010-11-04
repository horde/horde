<?php
/**
 * Horde web configuration script.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

/**
 * Does an FTP upload to save the configuration.
 */
function _uploadFTP($params)
{
    global $registry, $notification;

    $params['hostspec'] = 'localhost';
    try {
        $vfs = VFS::factory('ftp', $params);
    } catch (VFS_Exception $e) {
        $notification->push(sprintf(_("Could not connect to server \"%s\" using FTP: %s"), $params['hostspec'], $e->getMessage()), 'horde.error');
        return false;
    }

    /* Loop through the config and write to FTP. */
    $no_errors = true;
    foreach ($session->get('horde', 'config/') as $app => $config) {
        $path = $registry->get('fileroot', $app) . '/config';
        /* Try to back up the current conf.php. */
        if ($vfs->exists($path, 'conf.php')) {
            try {
                $vfs->rename($path, 'conf.php', $path, '/conf.bak.php');
                $notification->push(_("Successfully saved backup configuration."), 'horde.success');
            } catch (VFS_Exception $e) {
                $notification->push(sprintf(_("Could not save a backup configuation: %s"), $e->getMessage()), 'horde.error');
            }
        }

        try {
            $vfs->writeData($path, 'conf.php', $config);
            $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
            $session->remove('horde', 'config/' . $app);
        } catch (VFS_Exception $e) {
            $no_errors = false;
            $notification->push(sprintf(_("Could not write configuration for \"%s\": %s"), $app, $e->getMessage()), 'horde.error');
        }
    }
    $registry->clearCache();
    return $no_errors;
}

$hconfig = new Horde_Config();

/* Check for versions if requested. */
$versions = array();
if (Horde_Util::getFormData('check_versions')) {
    try {
        $versions = $hconfig->checkVersions();
    } catch (Horde_Exception $e) {
        $notification->push(_("Could not contact server. Try again later."), 'horde.error');
    }
}

/* Set up some icons. */
$success = Horde::img('alerts/success.png');
$warning = Horde::img('alerts/warning.png');
$error = Horde::img('alerts/error.png');

$conf_url = Horde::url('admin/config/config.php');
$a = $registry->listAllApps();
$apps = array();
$i = -1;
if (file_exists(HORDE_BASE . '/lib/bundle.php')) {
    include HORDE_BASE . '/lib/bundle.php';
    $apps[0] = array('sort' => '00',
                     'name' => '<strong>' . BUNDLE_FULLNAME . '</strong>',
                     'icon' => Horde::img($registry->get('icon', 'horde'),
                                          BUNDLE_FULLNAME, '', ''),
                     'version' => '<strong>' . BUNDLE_VERSION . '</strong>');
    if (!empty($versions)) {
        if (!isset($versions[BUNDLE_NAME])) {
            $apps[0]['load'] = $warning;
            $apps[0]['vstatus'] = _("No stable version exists yet.");
        } elseif (version_compare($versions[BUNDLE_NAME]['version'], BUNDLE_VERSION, '>')) {
            $apps[0]['load'] = $error;
            $apps[0]['vstatus'] = Horde::link($versions[BUNDLE_NAME]['url'], sprintf(_("Download %s"), BUNDLE_FULLNAME)) . sprintf(_("A newer version (%s) exists."), $versions[BUNDLE_NAME]['version']) . '</a> ';
        } else {
            $apps[0]['load'] = $success;
            $apps[0]['vstatus'] = _("Application is up-to-date.");
        }
    }
    $i++;
}

foreach ($a as $app) {
    /* Skip app if no conf.xml file. */
    $path = $registry->get('fileroot', $app) . '/config';
    if (!file_exists($path . '/conf.xml')) {
        continue;
    }

    $i++;
    $path = $registry->get('fileroot', $app) . '/config';

    $conf_link = Horde::link($conf_url->copy()->add('app', $app), sprintf(_("Configure %s"), $app));
    $apps[$i]['sort'] = $registry->get('name', $app) . ' (' . $app . ')';
    $apps[$i]['name'] = $conf_link . $apps[$i]['sort'] . '</a>';
    $apps[$i]['icon'] = Horde::img($registry->get('icon', $app), $registry->get('name', $app), '', '');
    $apps[$i]['version'] = '';
    if ($version = $registry->getVersion($app, true)) {
        $apps[$i]['version'] = $version;
        if (!empty($versions)) {
            if (!isset($versions[$app])) {
                $apps[$i]['load'] = $warning;
                $apps[$i]['vstatus'] = _("No stable version exists yet.");
            } elseif (version_compare(preg_replace('/H\d \((.*)\)/', '$1', $versions[$app]['version']), $apps[$i]['version'], '>')) {
                $apps[$i]['load'] = $error;
                $apps[$i]['vstatus'] = Horde::link($versions[$app]['url'], sprintf(_("Download %s"), $app)) . sprintf(_("A newer version (%s) exists."), $versions[$app]['version']) . '</a> ';
            } else {
                $apps[$i]['load'] = $success;
                $apps[$i]['vstatus'] = _("Application is up-to-date.");
            }
        }
    }

    if (!file_exists($path . '/conf.php')) {
        /* No conf.php exists. */
        $apps[$i]['conf'] = $conf_link . $error . '</a>';
        $apps[$i]['status'] = _("Missing configuration. You must generate it before using this application.");
    } else {
        /* A conf.php exists, get the xml version. */
        if (($xml_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.xml'))) === false) {
            $apps[$i]['conf'] = $conf_link . $warning . '</a>';
            $apps[$i]['status'] = _("No version found in original configuration. Regenerate configuration.");
            continue;
        }
        /* Get the generated php version. */
        if (($php_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.php'))) === false) {
            /* No version found in generated php, suggest regenarating
             * just in case. */
            $apps[$i]['conf'] = $conf_link . $warning . '</a>';
            $apps[$i]['status'] = _("No version found in your configuration. Regenerate configuration.");
            continue;
        }

        if ($xml_ver != $php_ver) {
            /* Versions are not the same, configuration is out of date. */
            $apps[$i]['conf'] = $conf_link . $error . '</a>';
            $apps[$i]['status'] = _("Configuration is out of date.");
            continue;
        } else {
            /* Configuration is ok. */
            $apps[$i]['conf'] = $conf_link . $success . '</a>';
            $apps[$i]['status'] = _("Application is ready.");
        }
    }
}

/* Sort the apps by name. */
Horde_Array::arraySort($apps, 'sort');

/* Set up any actions that may be offered. */
$actions = array();
$ftpform = '';
if ($session->get('horde', 'config/')) {
    $url = Horde::url('admin/config/diff.php');
    $action = _("Show differences between currently saved and the newly generated configuration.");
    $actions[] = array('icon' => Horde::img('search.png', '', 'align="middle"'),
                       'link' => Horde::link('#', '', '', '', Horde::popupJs($url, array('height' => 480, 'width' => 640, 'urlencode' => true)) . 'return false;') . $action . '</a>');

    /* Action to download the configuration upgrade PHP script. */
    $url = Horde::url('admin/config/scripts.php')->add(array('setup' => 'conf', 'type' => 'php'));
    $action = _("Download generated configuration as PHP script.");
    $actions[] = array('icon' => Horde::img('download.png', '', 'align="middle"'),
                       'link' => Horde::link($url) . $action . '</a>');
    /* Action to save the configuration upgrade PHP script. */
    $action = _("Save generated configuration as a PHP script to your server's temporary directory.");
    $actions[] = array('icon' => Horde::img('save.png', '', 'align="middle"'),
                       'link' => Horde::link($url->add('save', 'tmp')) . $action . '</a>');

    /* Set up the form for FTP upload of scripts. */
    $vars = Horde_Variables::getDefaultVariables();
    $ftpform = new Horde_Form($vars);
    $ftpform->setButtons(_("Upload"), true);
    $ftpform->addVariable(_("Username"), 'username', 'text', true, false, null, array('', 20));
    $ftpform->addVariable(_("Password"), 'password', 'password', false);

    if ($ftpform->validate($vars)) {
        $ftpform->getInfo($vars, $info);
        $upload = _uploadFTP($info);
        if ($upload) {
            $notification->push(_("Uploaded all application configuration files to the server."), 'horde.success');
            Horde::url('admin/config/index.php', true)->redirect();
        }
    }
    /* Render the form. */
    Horde::startBuffer();
    $ftpform->renderActive(new Horde_Form_Renderer(), $vars, 'index.php', 'post');
    $ftpform = Horde::endBuffer();
}

if (file_exists(Horde::getTempDir() . '/horde_configuration_upgrade.php')) {
    /* Action to remove the configuration upgrade PHP script. */
    $url = Horde::url('admin/config/scripts.php')->add('clean', 'tmp');
    $action = _("Remove saved script from server's temporary directory.");
    $actions[] = array('icon' => Horde::img('delete.png', '', 'align="middle"'),
                       'link' => Horde::link($url) . $action . '</a>');
}

/* Set up the template. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('versions', !empty($versions), true);
$template->set('version_action', Horde::url('admin/config/index.php'));
$template->set('version_input', Horde_Util::formInput());
$template->set('apps', $apps);
$template->set('actions', $actions, true);
$template->set('ftpform', $ftpform, true);

$title = sprintf(_("%s Configuration"), $registry->get('name', 'horde'));
Horde::addScriptFile('stripe.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $template->fetch(HORDE_TEMPLATES . '/admin/config/index.html');
require HORDE_TEMPLATES . '/common-footer.inc';
