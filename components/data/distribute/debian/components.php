<?php

$component = $this->_config->getComponent();

$options = $this->_config->getOptions();
if (isset($options['destination'])) {
    $destination = $options['destination'];
} else {
    $destination = getcwd();
}

if (in_array($component->getName(), array('content', 'horde', 'imp', 'ingo', 'kronolith', 'mnemo', 'nag', 'timeobjects', 'turba', 'webmail'))) {
    $package_name = 'horde-' . $component->getName();
} else {
    $package_name = $component->getName();
}
$package_name = strtr(strtolower($package_name), '_', '-');

$destination .= '/php-' . $package_name . '-' . $component->getVersion();

$component->placeArchive($destination);

system('cd ' . $destination . ' && tar zxpf ' . $component->getArchiveName());

$build_template = new Components_Helper_Templates_Directory(
    $this->_config_application->getTemplateDirectory() . '/templates',
    $destination . '/debian'
);
$build_template->write(
    array(
        'name' => $package_name,
        'horde_name' => $component->getName(),
        'version' => $component->getVersion()
    )
);

system('cd ' . $destination . ' && dpkg-buildpackage');
