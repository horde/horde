<?php

$component = $this->_config->getComponent();

$options = $this->_config->getOptions();
if (isset($options['destination'])) {
    $destination = $options['destination'];
} else {
    $destination = getcwd();
}


$applications = array(
    'content',
    'horde',
    'imp',
    'ingo',
    'kronolith',
    'mnemo',
    'nag',
    'timeobjects',
    'turba',
    'webmail'
);

$bundles = array(
    'groupware',
    'webmail',
    'kolab_webmail'
);

function processDependencies($component) {

    $buildRequires = array();
    $requires = array();
    $suggests = array();

    // currently we always need the horde channel and sometimes pear - but pear is a default
    $channel  = 'php5-pear-channel-horde';
    $buildRequires[$channel] = $channel;
    $requires[$channel] = $channel;

    foreach ($component->getDependencies() as $dependency) {
        if ($dependency['type'] == 'php') {
            $name = 'php5';
            $requires[$name]      = "$name >= " . $dependency['version'];
            $buildRequires[$name] = "$name >= " . $dependency['version'];
        } elseif ($dependency['name'] == 'PEAR') {
            $name = 'php5-pear';
            $requires[$name]      = "$name >= " . $dependency['version'];
            $buildRequires[$name] = "$name >= " . $dependency['version'];
        } elseif (!empty($dependency['channel']) &&
            $dependency['channel'] == 'pear.horde.org') {

            // library or app naming scheme?
            if (preg_match('/Horde_/', $dependency['name'])) {
                $name = 'php5-pear-' . $dependency['name'];
            } elseif ($dependency['name'] == 'horde') {
                $name = 'horde4';
            } else {
                $name = 'horde4-' . $dependency['name'];
            }
            if ($dependency['optional'] == 'yes') {
                $suggests[$name] = "$name >= " . $dependency['min'];
            } else {
                $requires[$name]      = "$name >= " . $dependency['min'];
                $buildRequires[$name] = "$name >= " . $dependency['min'];
            }
        } elseif (!empty($dependency['channel']) &&
            $dependency['channel'] == 'pear.php.net') {
            $name = 'php5-pear-' . $dependency['name'];
            if ($dependency['optional'] == 'yes') {
                $suggests[$name] = "$name >= " . $dependency['min'];
            } else {
                $requires[$name] = "$name >= " . $dependency['min'];
                $buildRequires[$name] = "$name >= " . $dependency['min'];
            }
        } elseif ($dependency['type'] == 'ext') {
            // TODO: we need to prepend a blacklist here
            // Some extensions are part of the php5 package
            $name = 'php5-' . $dependency['name'];
            if ($dependency['optional'] == 'yes') {
                $suggests[$name] = $name;
            } else {
                $requires[$name]      = $name;
                $buildRequires[$name] = $name;
            }
        }
    }
    $output = "#Build time requirements\n";
    foreach ($buildRequires as $line) {
        $output .= sprintf("%-15s %s\n", 'BuildRequires:', $line);
    }
    $output .= "#Install time requirements\n";
    foreach ($requires as $line) {
        $output .= sprintf("%-15s %s\n", 'Requires:', $line);
    }
    $output .= "#optional packages for enhanced features\n";
    foreach ($suggests as $line) {
        $output .= sprintf("%-15s %s\n", 'Suggests:', $line);
    }
    return $output;
}


if (in_array($component->getName(), $applications)) {
    $package_name = 'horde4-' .  $component->getName();
} elseif (in_array($component->getName(), $bundles)) {
    throw new Components_Exception("Bundles are not supported in openSUSE");
} else {
    $package_name = 'php5-pear-' .  $component->getName();
}

$package_version = $component->getVersion();

$destination .= '/server:php:applications/' . $package_name;

$archive = array_shift(
    $component->placeArchive($destination, array("logger" => $this->_output, 'keep_version' => true))
);

if (!file_exists($destination)) {
    mkdir($destination, 0700, true);
}

$t_dirs = array(
    $this->_config_application->getTemplateDirectory() . '/templates'
);
if (file_exists($t_dirs[0] . '-' . $package_name)) {
    $t_dirs[] = $t_dirs[0] . '-' . $package_name;
}
if (file_exists($t_dirs[0] . '-' . $package_name . '-' . $package_version)) {
    $t_dirs[] = $t_dirs[0] . '-' . $package_name . '-' . $package_version;
}

foreach ($t_dirs as $template_directory) {
    $build_template = new Components_Helper_Templates_RecursiveDirectory(
        $template_directory,
        $destination
    );
    $build_template->write(
        array(
            'name' => $package_name,
            'version' => $package_version,
            'component' => $component,
            'applications' => $applications,
            'bundles' => $bundles
        )
    );
}
// build a text containing only the last change. Use dash instead of *

$changes = "updated to version $package_version\n- [xxx] something changed\n- [yyy] This changed too" . $component->getChangelog(new Components_Helper_ChangeLog($this->_output));
//$changes = $component->getInstallationFileList();
shell_exec("cd $destination && mv package.spec $package_name.spec && osc vc -m \"$changes\" &> file");