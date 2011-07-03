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

if (in_array($component->getName(), $applications)) {
    $package_name = 'horde-' . $component->getName();
} else {
    $package_name = $component->getName();
}
$package_name = strtr(strtolower($package_name), '_', '-');

$component->placeArchive($destination);

$destination .= '/php-' . $package_name . '-' . $component->getVersion();

if (!file_exists($destination)) {
    mkdir($destination, 0700, true);
}

system('cd ' . $destination . ' && tar xzpf ../' . $component->getArchiveName());

$build_template = new Components_Helper_Templates_Directory(
    $this->_config_application->getTemplateDirectory() . '/templates',
    $destination . '/debian'
);
$build_template->write(
    array(
        'name' => $package_name,
        'component' => $component,
        'applications' => $applications
    )
);

system('cd ' . $destination . ' && dpkg-buildpackage');
