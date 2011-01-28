<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */

/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */
class Koward_Test extends Horde_Kolab_Test_Storage
{
    /**
     * Prepare the configuration.
     *
     * @return NULL
     */
    public function prepareConfiguration()
    {
        $fh = fopen(HORDE_BASE . '/config/conf.php', 'w');
        $data = <<<EOD
\$conf['use_ssl'] = 2;
\$conf['server']['name'] = \$_SERVER['SERVER_NAME'];
\$conf['server']['port'] = \$_SERVER['SERVER_PORT'];
\$conf['debug_level'] = E_ALL;
\$conf['umask'] = 077;
\$conf['compress_pages'] = true;
\$conf['menu']['always'] = false;
\$conf['portal']['fixed_blocks'] = array();
\$conf['imsp']['enabled'] = false;

/** Additional config variables required for a clean Horde configuration */
\$conf['session']['use_only_cookies'] = false;
\$conf['session']['timeout'] = 0;
\$conf['cookie']['path'] = '/';
\$conf['cookie']['domain'] = \$_SERVER['SERVER_NAME'];
\$conf['use_ssl'] = false;
\$conf['session']['cache_limiter'] = 'nocache';
\$conf['session']['name'] = 'Horde';
\$conf['log']['enabled'] = false;
\$conf['prefs']['driver'] = 'session';
\$conf['auth']['driver'] = 'kolab';
\$conf['share']['driver'] = 'kolab';
\$conf['server']['token_lifetime'] = 3600;
\$conf['debug_level'] = E_ALL;

/** Make the share driver happy */
\$conf['kolab']['enabled'] = true;

/** Ensure we still use the LDAP test driver */
\$conf['kolab']['server']['driver'] = 'test';

/** Ensure that we do not trigger on folder update */
\$conf['kolab']['no_triggering'] = true;

/** Storage location for the free/busy system */
\$conf['fb']['cache_dir']             = '/tmp';
\$conf['kolab']['freebusy']['server'] = 'https://fb.example.org/freebusy';

/** Setup the virtual file system for Kolab */
\$conf['vfs']['params']['all_folders'] = true;
\$conf['vfs']['type'] = 'kolab';

\$conf['kolab']['ldap']['phpdn'] = null;
\$conf['fb']['use_acls'] = true;
EOD;
        fwrite($fh, "<?php\n" . $data);
        fclose($fh);
    }

    /**
     * Prepare the registry.
     *
     * @return NULL
     */
    public function prepareRegistry()
    {
        $fh = fopen(HORDE_BASE . '/config/registry.php', 'w');
        $data = <<<EOD
\$this->applications['horde'] = array(
    'fileroot' => dirname(__FILE__) . '/..',
    'webroot' => '/',
    'initial_page' => 'login.php',
    'name' => _("Horde"),
    'status' => 'active',
    'templates' => dirname(__FILE__) . '/../templates',
    'provides' => 'horde',
);

\$this->applications['koward'] = array(
    'fileroot' => KOWARD_BASE,
    'webroot' => \$this->applications['horde']['webroot'] . '/koward',
    'name' => _("Koward"),
    'status' => 'active',
    'initial_page' => 'index.php',
);
EOD;
        fwrite($fh, "<?php\n" . $data);
        fclose($fh);
        if (!file_exists(HORDE_BASE . '/config/registry.d')) {
            mkdir(HORDE_BASE . '/config/registry.d');
        }
    }

    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runGiven($world, $action, $arguments);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runWhen($world, $action, $arguments);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runThen($world, $action, $arguments);
        }
    }

}
