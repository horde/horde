<?php
/**
 * Horde web setup script.
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
    $vfs = VFS::singleton('ftp', $params);
    if (is_a($vfs, 'PEAR_Error')) {
        $notification->push(sprintf(_("Could not connect to server \"%s\" using FTP: %s"), $params['hostspec'], $vfs->getMessage()), 'horde.error');
        return false;
    }

    /* Loop through the config and write to FTP. */
    $no_errors = true;
    foreach ($_SESSION['_config'] as $app => $config) {
        $path = $registry->get('fileroot', $app) . '/config';
        /* Try to back up the current conf.php. */
        if ($vfs->exists($path, 'conf.php')) {
            if (($result = $vfs->rename($path, 'conf.php', $path, '/conf.bak.php')) === true) {
                $notification->push(_("Successfully saved backup configuration."), 'horde.success');
            } elseif (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("Could not save a backup configuation: %s"), $result->getMessage()), 'horde.error');
            } else {
                $notification->push(_("Could not save a backup configuation."), 'horde.error');
            }
        }

        $write = $vfs->writeData($path, 'conf.php', $config);
        if (is_a($write, 'PEAR_Error')) {
            $no_errors = false;
            $notification->push(sprintf(_("Could not write configuration for \"%s\": %s"), $app, $write->getMessage()), 'horde.error');
        } else {
            $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
            unset($_SESSION['_config'][$app]);
        }
    }
    $registry->clearCache();
    return $no_errors;
}

/* Check for versions if requested. */
$versions = array();
if (Horde_Util::getFormData('check_versions')) {
    require_once 'Horde/DOM.php';
    $http = new HTTP_Request('http://www.horde.org/versions.php');
    $result = $http->sendRequest();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($http->getResponseCode() != 200) {
        $notification->push(_("Unexpected response from server, try again later."), 'horde.error');
    } else {
        $dom = Horde_DOM_Document::factory(array('xml' => $http->getResponseBody()));
        $stable = $dom->get_elements_by_tagname('stable');
        if (!count($stable) || !$stable[0]->has_child_nodes()) {
            $notification->push(_("Invalid response from server."), 'horde.error');
        } else {
            for ($app = $stable[0]->first_child();
                 !empty($app);
                 $app = $app->next_sibling()) {
                if (!is_a($app, 'domelement') &&
                    !is_a($app, 'Horde_DOM_Element')) {
                    continue;
                }
                $version = $app->get_elements_by_tagname('version');
                $url = $app->get_elements_by_tagname('url');
                $versions[$app->get_attribute('name')] = array(
                    'version' => $version[0]->get_content(),
                    'url' => $url[0]->get_content());
            }
        }
    }
}

/* Set up some icons. */
$success = Horde::img('alerts/success.png');
$warning = Horde::img('alerts/warning.png');
$error = Horde::img('alerts/error.png');

$conf_url = Horde::applicationUrl('admin/setup/config.php');
$a = $registry->listApps(array('inactive', 'hidden', 'notoolbar', 'active', 'admin'));
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
    if ($version = $registry->getVersion($app)) {
        $apps[$i]['version'] = $version;
        if (!empty($versions)) {
            if (!isset($versions[$app])) {
                $apps[$i]['load'] = $warning;
                $apps[$i]['vstatus'] = _("No stable version exists yet.");
            } elseif (version_compare(preg_replace('/H\d \((.*)\)/', '$1', $versions[$app]['version']), preg_replace('/H\d \((.*)\)/', '$1', $apps[$i]['version']), '>')) {
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
        if (($xml_ver = Horde_Config::getVersion(@file_get_contents($path . '/conf.xml'))) === false) {
            $apps[$i]['conf'] = $conf_link . $warning . '</a>';
            $apps[$i]['status'] = _("No version found in original configuration. Regenerate configuration.");
            continue;
        }
        /* Get the generated php version. */
        if (($php_ver = Horde_Config::getVersion(@file_get_contents($path . '/conf.php'))) === false) {
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
if (!empty($_SESSION['_config'])) {
    $url = Horde::applicationUrl('admin/setup/diff.php');
    $action = _("Show differences between currently saved and the newly generated configuration.");
    $actions[] = array('icon' => Horde::img('search.png', '', 'align="middle"', $registry->getImageDir('horde')),
                       'link' => Horde::link('#', '', '', '', Horde::popupJs($url, array('height' => 480, 'width' => 640, 'urlencode' => true)) . 'return false;') . $action . '</a>');

    /* Action to download the configuration upgrade PHP script. */
    $url = Horde::applicationUrl('admin/setup/scripts.php')->add(array('setup' => 'conf', 'type' => 'php'));
    $action = _("Download generated configuration as PHP script.");
    $actions[] = array('icon' => Horde::img('download.png', '', 'align="middle"', $registry->getImageDir('horde')),
                       'link' => Horde::link($url) . $action . '</a>');
    /* Action to save the configuration upgrade PHP script. */
    $action = _("Save generated configuration as a PHP script to your server's temporary directory.");
    $actions[] = array('icon' => Horde::img('save.png', '', 'align="middle"', $registry->getImageDir('horde')),
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
            $notification->push(_("Uploaded all application setup files to the server."), 'horde.success');
            $url = Horde::applicationUrl('admin/setup/index.php', true);
            header('Location: ' . $url);
            exit;
        }
    }
    /* Render the form. */
    $ftpform = Horde_Util::bufferOutput(array($ftpform, 'renderActive'), new Horde_Form_Renderer(), $vars, 'index.php', 'post');
}

if (file_exists(Horde::getTempDir() . '/horde_setup_upgrade.php')) {
    /* Action to remove the configuration upgrade PHP script. */
    $url = Horde::applicationUrl('admin/setup/scripts.php')->add('clean', 'tmp');
    $action = _("Remove saved script from server's temporary directory.");
    $actions[] = array('icon' => Horde::img('delete.png', '', 'align="middle"', $registry->getImageDir('horde')),
                       'link' => Horde::link($url) . $action . '</a>');
}

/* Set up the template. */
$template = new Horde_Template();
$template->setOption('gettext', true);
$template->set('versions', !empty($versions), true);
$template->set('version_action', Horde::applicationUrl('admin/setup/index.php'));
$template->set('version_input', Horde_Util::formInput());
$template->set('apps', $apps);
$template->set('actions', $actions, true);
$template->set('ftpform', $ftpform, true);

$title = sprintf(_("%s Setup"), $registry->get('name', 'horde'));
Horde::addScriptFile('stripe.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $template->fetch(HORDE_TEMPLATES . '/admin/setup/index.html');
require HORDE_TEMPLATES . '/common-footer.inc';
