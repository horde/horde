<?php
/**
 * Horde web configuration script.
 *
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

/**
 * Does an FTP upload to save the configuration.
 */
function _uploadFTP($params)
{
    global $registry, $notification;

    $params['hostspec'] = 'localhost';
    try {
        $vfs = Horde_Vfs::factory('ftp', $params);
    } catch (Horde_Vfs_Exception $e) {
        $notification->push(sprintf(_("Could not connect to server \"%s\" using FTP: %s"), $params['hostspec'], $e->getMessage()), 'horde.error');
        return false;
    }

    /* Loop through the config and write to FTP. */
    $no_errors = true;
    foreach ($GLOBALS['session']->get('horde', 'config/') as $app => $config) {
        $path = $registry->get('fileroot', $app) . '/config';
        /* Try to back up the current conf.php. */
        if ($vfs->exists($path, 'conf.php')) {
            try {
                $vfs->rename($path, 'conf.php', $path, '/conf.bak.php');
                $notification->push(_("Successfully saved backup configuration."), 'horde.success');
            } catch (Horde_Vfs_Exception $e) {
                $notification->push(sprintf(_("Could not save a backup configuation: %s"), $e->getMessage()), 'horde.error');
            }
        }

        try {
            $vfs->writeData($path, 'conf.php', $config);
            $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
            $GLOBALS['session']->remove('horde', 'config/' . $app);
        } catch (Horde_Vfs_Exception $e) {
            $no_errors = false;
            $notification->push(sprintf(_("Could not write configuration for \"%s\": %s"), $app, $e->getMessage()), 'horde.error');
        }
    }

    $registry->rebuild();

    return $no_errors;
}

$hconfig = new Horde_Config();
$migration = new Horde_Core_Db_Migration(dirname(__FILE__) . '/../../..');
$vars = Horde_Variables::getDefaultVariables();
$a = $registry->listAllApps();

/* Check for versions if requested. */
$versions = array();
if ($vars->check_versions) {
    try {
        $versions = $hconfig->checkVersions();
    } catch (Horde_Exception $e) {
        $notification->push(_("Could not contact server. Try again later."), 'horde.error');
    }
}

/* Update configurations if requested. */
if ($vars->action == 'config') {
    foreach ($a as $app) {
        $path = $registry->get('fileroot', $app) . '/config';
        if (!file_exists($path . '/conf.xml') ||
            (file_exists($path . '/conf.php') &&
             ($xml_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.xml'))) !== false &&
             ($php_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.php'))) !== false &&
             $xml_ver == $php_ver)) {
            continue;
        }
        $vars = new Horde_Variables();
        $form = new Horde_Config_Form($vars, $app, true);
        $form->setSubmitted(true);
        if ($form->validate($vars)) {
            $config = new Horde_Config($app);
            $configFile = $config->configFile();
            if ($config->writePHPConfig($vars)) {
                $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($configFile)), 'horde.success');
            } else {
                $notification->push(sprintf(_("Could not save the configuration file %s. Use one of the options below to save the code."), Horde_Util::realPath($configFile)), 'horde.warning', array('content.raw'));
            }
        } else {
            $notification->push(sprintf(_("The configuration for %s cannot be updated automatically. Please update the configuration manually."), $app), 'horde.error');
        }
    }
}

/* Update schema if requested. */
if ($vars->action == 'schema') {
    $apps = isset($vars->app) ? array($vars->app) : $migration->apps;
    foreach ($apps as $app) {
        $migrator = $migration->getMigrator($app);
        if ($migrator->getTargetVersion() <= $migrator->getCurrentVersion()) {
            continue;
        }
        try {
            $migrator->up();
            $notification->push(sprintf(_("Updated schema for %s."), $app), 'horde.success');
        } catch (Exception $e) {
            $notification->push($e);
        }
    }
}

/* Set up some icons. */
$success = Horde::img('alerts/success.png');
$warning = Horde::img('alerts/warning.png');
$error = Horde::img('alerts/error.png');

$self_url = Horde::url('admin/config/');
$conf_url = Horde::url('admin/config/config.php');
$apps = $libraries = array();
$i = -1;
$config_outdated = $schema_outdated = false;
if (class_exists('Horde_Bundle')) {
    $apps[0] = array('sort' => '00',
                     'name' => '<strong>' . Horde_Bundle::FULLNAME . '</strong>',
                     'icon' => Horde::img($registry->get('icon', 'horde'),
                                          Horde_Bundle::FULLNAME, '', ''),
                     'version' => '<strong>' . Horde_Bundle::VERSION . '</strong>');
    if (!empty($versions)) {
        if (!isset($versions[Horde_Bundle::NAME])) {
            $apps[0]['load'] = $warning;
            $apps[0]['vstatus'] = _("No stable version exists yet.");
        } elseif (version_compare($versions[Horde_Bundle::NAME]['version'], Horde_Bundle::VERSION, '>')) {
            $apps[0]['load'] = $error;
            $apps[0]['vstatus'] = Horde::link($versions[Horde_Bundle::NAME]['url'], sprintf(_("Download %s"), Horde_Bundle::FULLNAME), '', '_blank') . sprintf(_("A newer version (%s) exists."), $versions[Horde_Bundle::NAME]['version']) . '</a> ';
        } else {
            $apps[0]['load'] = $success;
            $apps[0]['vstatus'] = _("Application is up-to-date.");
        }
    }
    $i++;
}

foreach ($a as $app) {
    $path = $registry->get('fileroot', $app) . '/config';
    if (!is_dir($path)) {
        continue;
    }

    $i++;
    $conf_link = $conf_url
        ->add('app', $app)
        ->link(array('title' => sprintf(_("Configure %s"), $app)));
    $db_link = $self_url
        ->add(array('app' => $app, 'action' => 'schema'))
        ->link(array('title' => sprintf(_("Update %s schema"), $app)));
    $apps[$i]['sort'] = $app;
    if ($name = $registry->get('name', $app)) {
        $apps[$i]['sort'] = $name . ' (' . $apps[$i]['sort'] . ')';
    }
    if (file_exists($path . '/conf.xml')) {
        $apps[$i]['name'] = $conf_link . $apps[$i]['sort'] . '</a>';
    } else {
        $apps[$i]['name'] = $apps[$i]['sort'];
    }
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
                $apps[$i]['vstatus'] = Horde::link($versions[$app]['url'], sprintf(_("Download %s"), $app), '', '_blank') . sprintf(_("A newer version (%s) exists."), $versions[$app]['version']) . '</a> ';
            } else {
                $apps[$i]['load'] = $success;
                $apps[$i]['vstatus'] = _("Application is up-to-date.");
            }
        }
    }

    if (file_exists($path . '/conf.xml')) {
        if (!file_exists($path . '/conf.php')) {
            /* No conf.php exists. */
            $apps[$i]['conf'] = $conf_link . $error . '</a>';
            $apps[$i]['status'] = $conf_link . _("Missing configuration.") . '</a>';
            $config_outdated = true;
        } else {
            /* A conf.php exists, get the xml version. */
            if (($xml_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.xml'))) === false) {
                $apps[$i]['conf'] = $conf_link . $warning . '</a>';
                $apps[$i]['status'] = $conf_link . _("No version found in original configuration. Regenerate configuration.") . '</a>';
                $config_outdated = true;
                continue;
            }
            /* Get the generated php version. */
            if (($php_ver = $hconfig->getVersion(@file_get_contents($path . '/conf.php'))) === false) {
                /* No version found in generated php, suggest regenerating just in
                 * case. */
                $apps[$i]['conf'] = $conf_link . $warning . '</a>';
                $apps[$i]['status'] = $conf_link . _("No version found in your configuration. Regenerate configuration.") . '</a>';
                $config_outdated = true;
                continue;
            }

            if ($xml_ver != $php_ver) {
                /* Versions are not the same, configuration is out of date. */
                $apps[$i]['conf'] = $conf_link . $error . '</a>';
                $apps[$i]['status'] = $conf_link . _("Configuration is out of date.") . '</a>';
                $config_outdated = true;
            } else {
                /* Configuration is ok. */
                $apps[$i]['conf'] = $conf_link . $success . '</a>';
                $apps[$i]['status'] = _("Application is ready.");
            }
        }
    }

    if (in_array($app, $migration->apps)) {
        /* If a DB backend hasn't been configured (yet), an exception will be
         * thrown. This is fine if this is the intial configuration, or if no
         * DB will be used. */
        try {
            $migrator = $migration->getMigrator($app);
        } catch (Horde_Exception $e) {
            $apps[$i]['db'] = $warning;
            $apps[$i]['dbstatus'] = _("DB access is not configured.");
            continue;
        }
        if ($migrator->getTargetVersion() > $migrator->getCurrentVersion()) {
            /* Schema is out of date. */
            $apps[$i]['db'] = $db_link . $error . '</a>';
            $apps[$i]['dbstatus'] = $db_link . _("DB schema is out of date.") . '</a>';
            $schema_outdated = true;
        } else {
            /* Schema is ok. */
            $apps[$i]['db'] = $success;
            $apps[$i]['dbstatus'] = _("DB schema is ready.");
        }
    }
}

/* Search for outdated library schemas. */
foreach ($migration->apps as $app) {
    if (in_array($app, $a)) {
        continue;
    }
    $i++;

    $db_link = $self_url
        ->add(array('app' => $app, 'action' => 'schema'))
        ->link(array('title' => sprintf(_("Update %s schema"), $app)));

    $apps[$i]['sort'] = 'ZZZ' . $app;
    $apps[$i]['name'] = implode('_', array_map(array('Horde_String', 'ucfirst'), explode('_', $app)));
    $apps[$i]['version'] = '';

    /* If a DB backend hasn't been configured (yet), an exception will be
     * thrown. This is fine if this is the intial configuration, or if no DB
     * will be used. */
    try {
        $migrator = $migration->getMigrator($app);
    } catch (Horde_Exception $e) {
        $apps[$i]['db'] = $warning;
        $apps[$i]['dbstatus'] = _("DB access is not configured.");
        continue;
    }

    if ($migrator->getTargetVersion() > $migrator->getCurrentVersion()) {
        /* Schema is out of date. */
        $apps[$i]['db'] = $db_link . $error . '</a>';
        $apps[$i]['dbstatus'] = $db_link . _("DB schema is out of date.") . '</a>';
        $schema_outdated = true;
    } else {
        /* Schema is ok. */
        $apps[$i]['db'] = $success;
        $apps[$i]['dbstatus'] = _("DB schema is ready.");
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
    $ftpform->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('admin/config/index.php'), 'post');
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
$template->set('config_outdated', $config_outdated && method_exists('Horde_Config', 'writePHPConfig'));
$template->set('schema_outdated', $schema_outdated);
$template->set('apps', $apps);
$template->set('actions', $actions, true);
$template->set('ftpform', $ftpform, true);

$title = sprintf(_("%s Configuration"), $registry->get('name', 'horde'));
Horde::addScriptFile('stripe.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $template->fetch(HORDE_TEMPLATES . '/admin/config/index.html');
require HORDE_TEMPLATES . '/common-footer.inc';
