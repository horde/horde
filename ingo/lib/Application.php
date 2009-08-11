<?php
/**
 * Ingo application API.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */
class Ingo_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        return array(
            'title' => array(
                'ingo:allow_rules' => _("Allow Rules"),
                'ingo:max_rules' => _("Maximum Number of Rules")
            ),
            'tree' => array(
                'ingo' => array(
                    'allow_rules' => false,
                    'max_rules' => false
                )
            ),
            'type' => array(
                'ingo:allow_rules' => 'boolean',
                'ingo:max_rules' => 'int'
            )
        );
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ingo::getMenu();
    }

}
