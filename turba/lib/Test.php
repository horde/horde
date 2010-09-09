<?php
/**
 * This class provides the Turba configuration for the test script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Turba
 */
class Turba_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'mysql' => 'MySQL Support',
        'pgsql' => 'PostgreSQL Support',
        'mssql' => 'Microsoft SQL Support',
        'oci8' => 'Oracle Support',
        'odbc' => 'Unified ODBC Support',
        'ldap' => 'LDAP Support'
    );

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array();

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array(
        'Net_LDAP' => array(
            'error' => 'Net_LDAP is required when doing schema checks with LDAP address books.',
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/attributes.php' => null,
        'config/conf.php' => null,
        'config/mime_drivers.php' => null,
        'config/prefs.php' => null,
        'config/backends.php' => null
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array();

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
        $ret = '<h1>LDAP Support Test</h1>';

        $params = array(
            'server' => Horde_Util::getPost('server'),
            'port' => Horde_Util::getPost('port', 389),
            'basedn' => Horde_Util::getPost('basedn'),
            'user' => Horde_Util::getPost('user'),
            'passwd' => Horde_Util::getPost('passwd'),
            'filter' => Horde_Util::getPost('filter'),
            'proto' => Horde_Util::getPost('proto')
        );

        if (!empty($params['server']) &&
            !empty($params['basedn']) &&
            !empty($params['filter'])) {
            $ret .= $this->_doConnectionTest();
        }

        $self_url = Horde::selfUrl()->add('app', 'turba');

        Horde::startBuffer();
        require TURBA_TEMPLATES . '/test/ldapserver.inc';

        return $ret . Horde::endBuffer();
    }

    /**
     * Perform LDAP server support test.
     *
     * @param array $params  Connection parameters.
     *
     * @return string  HTML output.
     */
    protected function _doConnectionTest($params)
    {
        $ret .= 'server="' . htmlspecialchars($params['server']) . '" ' .
            'basedn="' . htmlspecialchars($params['basedn']) . '" ' .
            'filter="' . htmlspecialchars($params['filter']) . '"<br />';

        if (!empty($params['user'])) {
            $ret .= 'bind as user="' . htmlspecialchars($params['user']) . '"<br />';
        } else {
            $ret .= 'bind anonymously<br />';
        }

        $ldap = ldap_connect($params['server'], $params['port']);
        if ($ldap) {
            if (!empty($params['proto']) && ($params['proto'] == '3')) {
                ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            }

            if (!empty($params['user']) && !ldap_bind($ldap, $params['user'], $params['passwd'])) {
                $ret .= '<p>unable to bind as ' . htmlspecialchars($params['user']) . ' to LDAP server</p>';
                ldap_close($ldap);
                $ldap = '';
            } elseif (empty($params['user']) && !ldap_bind($ldap)) {
                $ret .= "<p>unable to bind anonymously to LDAP server</p>\n";
                ldap_close($ldap);
                $ldap = '';
            }

            if ($ldap) {
                $result = ldap_search($ldap, $params['basedn'], $params['filter']);
                if ($result) {
                    $ret .= '<p>search returned ' . ldap_count_entries($ldap, $result) . " entries</p>\n";
                    $info = ldap_get_entries($ldap, $result);
                    for ($i = 0; $i < $info['count']; ++$i) {
                        $ret .= '<p>dn is: ' . $info[$i]['dn'] . '<br />' .
                            'first cn entry is: ' . $info[$i]['cn'][0] . '<br />' .
                            'first mail entry is: ' . $info[$i]['mail'][0] . '</p>';

                        if ($i >= 10) {
                            $ret .= '<p>(only first 10 entries displayed)</p>';
                            break;
                        }
                    }
                } else {
                    $ret .= '<p>unable to search LDAP server</p>';
                }
            }
        } else {
            $ret .= '<p>unable to connect to LDAP server</p>';
        }

        return $ret;
    }

}
