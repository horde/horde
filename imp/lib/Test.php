<?php
/**
 * The IMP_Test:: class provides the IMP configuration for the test script.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array();

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array(
        'file_uploads'  =>  array(
            'error' => 'file_uploads must be enabled to use various features of IMP. See the INSTALL file for more information.',
            'setting' => true
        )
    );

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array(
        'Auth_SASL' => array(
            'error' => 'If your IMAP server uses CRAM-MD5 or DIGEST-MD5 authentication, this module is required.'
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You must generate this file as an administrator via Horde.  See horde/docs/INSTALL.',
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array(
        'gollem' => array(
            'error' => 'Gollem provides access to local VFS filesystems to attach files.',
            'version' => '2.0'
        ),
        'ingo' => array(
            'error' => 'Ingo provides basic mail filtering capabilities to IMP.',
            'version' => '2.0'
        ),
        'nag' => array(
            'error' => 'Nag allows tasks to be directly created from e-mail data.',
            'version' => '3.0'
        ),
        'turba' => array(
            'error' => 'Turba provides addressbook/contacts capabilities to IMP.',
            'version' => '3.0'
        )
    );

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
        $ret = '<h1>Mail Server Support Test</h1>';

        if (Horde_Util::getPost('user') && Horde_Util::getPost('passwd')) {
            $ret .= $this->_doConnectionTest();
        }

        $self_url = Horde::selfUrl()->add('app', 'imp');

        Horde::startBuffer();
        require IMP_TEMPLATES . '/test/mailserver.inc';

        return $ret . Horde::endBuffer();
    }

    /**
     * Perform mail server support test.
     *
     * @return string  HTML output.
     */
    protected function _doConnectionTest()
    {
        $imap_config = array(
            'username' => Horde_Util::getPost('user'),
            'password' => Horde_Util::getPost('passwd'),
            'hostspec' => Horde_Util::getPost('server'),
            'port' => Horde_Util::getPost('port'),
            'secure' => Horde_Util::getPost('encrypt')
        );

        $driver = (Horde_Util::getPost('server_type') == 'imap')
            ? 'Socket'
            : 'Socket_Pop3';

        try {
            $imap_client = Horde_Imap_Client::factory($driver, $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            return $this->_errorMsg($e);
        }

        $ret = '<strong>Attempting to login to the server:</strong> ';

        try {
            $imap_client->login();
        } catch (Horde_Imap_Client_Exception $e) {
            return $this->_errorMsg($e);
        }

        $ret .= '<span style="color:green">SUCCESS</span><p />';

        if ($driver == 'Socket') {
            $ret .= '<strong>The following IMAP server information was discovered from the remote server:</strong>' .
                '<blockquote><em>Namespace Information</em><blockquote><pre>';

            try {
                $namespaces = $imap_client->getNamespaces();
                foreach ($namespaces as $val) {
                    $ret .= 'NAMESPACE: "' . htmlspecialchars($val['name']) . "\"\n" .
                        'DELIMITER: ' . htmlspecialchars($val['delimiter']) . "\n" .
                        'TYPE: ' . htmlspecialchars($val['type']) . "\n\n";
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_errorMsg($e);
            }

            $ret .= '</pre></blockquote></blockquote>' .
                '<blockquote><em>IMAP server capabilities:</em><blockquote><pre>';

            try {
                foreach ($imap_client->capability() as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $val2) {
                            $ret .= htmlspecialchars($key) . '=' . htmlspecialchars($val2) . "\n";
                        }
                    } else {
                        $ret .= htmlspecialchars($key) . "\n";
                    }
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_errorMsg($e);
            }

            $ret .= '</pre></blockquote></blockquote>';

            try {
                $id_info = $imap_client->getID();
                if (!empty($id_info)) {
                    $ret .= '<blockquote><em>IMAP server information:</em><blockquote><pre>';
                    foreach ($id_info as $key => $val) {
                        $ret .= htmlspecialchars("$key:  $val") . "\n";
                    }
                    $ret .= '</pre></blockquote></blockquote>';
                }
            } catch (Horde_Imap_Client_Exception $e) {
                // Ignore a lack of the ID capability.
            }

            // @todo IMAP Charset Search Support
        }

        return $ret;
    }

    /**
     * Return error message from mail server testing.
     *
     * @return string  HTML output.
     */
    protected function _errorMsg($e)
    {
        return '<span style=\"color:red\">ERROR</span> - The server returned the following error message:' .
            '<pre>' . $e->getMessage() . '</pre><p />';
    }

}
