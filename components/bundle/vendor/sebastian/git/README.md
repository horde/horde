# Git

Simple PHP wrapper for Git.

## Installation

You can use the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php) or [Composer](http://getcomposer.org/) to download and install this package as well as its dependencies.

### PEAR Installer

The following two commands (which you may have to run as `root`) are all that is required to install this package using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/Git

### Composer

To add this package as a local, per-project dependency to your project, simply add a dependency on `sebastian/git` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file that just defines a dependency on Git 1.0:

    {
        "require": {
            "sebastian/git": "1.0.*"
        }
    }
