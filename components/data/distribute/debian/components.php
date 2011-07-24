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

$pkg_info = '/usr/share/pkg-php-tools/scripts/phppkginfo';
if (!is_executable($pkg_info)) {
    throw new Components_Exception(
        sprintf(
            'The file "%s" does not exists or is not executable!',
            $pkg_info
        )
    );
}
$package_name = shell_exec(
    $pkg_info . ' debian_pkgname pear.horde.org ' .
    escapeshellarg($component->getName())
);
$package_version = shell_exec(
    $pkg_info . ' debian_version ' .
    escapeshellarg($component->getVersion())
);

$archive = array_shift(
    $component->placeArchive($destination, array("logger" => $this->_output))
);

$destination .= '/' . $package_name . '-' . $package_version;

if (!file_exists($destination)) {
    mkdir($destination, 0700, true);
}

system('cd ' . $destination . ' && tar xzpf ' . $archive);

$build_template = new Components_Helper_Templates_Directory(
    $this->_config_application->getTemplateDirectory() . '/templates',
    $destination . '/debian'
);
$build_template->write(
    array(
        'name' => $package_name,
        'version' => $package_version,
        'component' => $component,
        'applications' => $applications
    )
);

// Properly name tarball to avoid building a native debian package
system('cd ' . $destination . ' && mv ' . $archive . ' ../' . $package_name . '_' . $package_version . '.orig.tar.gz');

system('cd ' . $destination . ' && dpkg-buildpackage');
