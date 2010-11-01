<?php
/**
 * XPPublisher Wizard
 * Builds a "Publish this file/folder to the web" handler for Windows XP+.
 *
 * @package Ansel
 * @author  Chuck Hagenbuch <chuck@horde.org>
 */
class Ansel_XPPublisher
{
    /**
     * Generates a Windows Registry file that a user can double-click
     * to add your provider to their list of Providers for the
     * Publishing Wizard.
     *
     * @param string $appKey The unique name of your application. If
     * people can use your application on multiple servers, make sure
     * to include a hostname or something else server-specific in this
     * string.
     *
     * @param string $displayName The name of your service in the
     * Providers list.
     *
     * @param string $description Shows up as the description (2nd
     * line) of your service in the Providers list.
     *
     * @param string $href The address of the wizard interface.
     *
     * @param string $icon The location of an icon for the
     * service. Usually displayed at 32x32, will be scaled if it's not
     * that size.
     */
    public function sendRegFile($appKey, $displayName, $description, $href, $icon)
    {
        $GLOBALS['browser']->downloadHeaders('install_registry.reg', 'application/octet-stream');

        $lines = array(
            'Windows Registry Editor Version 5.00',
            '',
            '[HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Explorer\PublishingWizard\PublishingWizard\Providers\\' . $appKey . ']',
            '"displayname"="' . $displayName . '"',
            '"description"="' . $description . '"',
            '"href"="' . $href . '"',
            '"icon"="' . $icon . '"');
        echo implode("\r\n", $lines) . "\r\n";
    }

}
