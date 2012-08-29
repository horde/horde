<?php
/**
 * The IMP_Test:: class provides the IMP configuration for the test script.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'zip' => array(
            'descrip' => 'Zip Support',
            'error' => 'The zip extension is used when importing compress mailboxes. IMP will fallback to using the zlib extension (if available) to read this data which should be sufficient for most installations. If zip support is desired, compile PHP with <code>--with-zip</code> to activate.'
        )
    );

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
    protected $_pearList = array();

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

        $vars = Horde_Variables::getDefaultVariables();
        if ($vars->user && $vars->passwd) {
            $ret .= $this->_doConnectionTest($vars);
        }

        $self_url = Horde::selfUrl()->add('app', 'imp');

        Horde::startBuffer();
        require IMP_TEMPLATES . '/test/mailserver.inc';

        return $ret . Horde::endBuffer();
    }

    /**
     * Perform mail server support test.
     *
     * @param Horde_Variables $vars  Variables object.
     *
     * @return string  HTML output.
     */
    protected function _doConnectionTest($vars)
    {
        $imap_config = array(
            'username' => $vars->user,
            'password' => $vars->passwd,
            'hostspec' => $vars->server,
            'port' => $vars->port,
            'secure' => $vars->encrypt
        );

        $driver = ($vars->server_type == 'imap')
            ? 'Horde_Imap_Client_Socket'
            : 'Horde_Imap_Client_Socket_Pop3';

        try {
            $imap_client = new $driver($imap_config);
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

        if ($driver == 'Horde_Imap_Client_Socket') {
            $ret .= '<strong>The following IMAP server information was discovered from the server:</strong>' .
                '<blockquote><em>Namespace Information</em><blockquote><pre>';

            try {
                $namespaces = $imap_client->getNamespaces();
                foreach ($namespaces as $val) {
                    switch ($val['type']) {
                    case Horde_Imap_Client::NS_PERSONAL:
                        $type = 'Personal';
                        break;

                    case Horde_Imap_Client::NS_OTHER:
                        $type = 'Other Users\'';
                        break;

                    case Horde_Imap_Client::NS_SHARED:
                        $type = 'Shared';
                        break;
                    }

                    $ret .= 'NAMESPACE: "' . htmlspecialchars($val['name']) . "\"\n" .
                        'DELIMITER: ' . htmlspecialchars($val['delimiter']) . "\n" .
                        'TYPE: ' . htmlspecialchars($type) . "\n\n";
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

            $ret .= '</pre></blockquote></blockquote>' .
                '<blockquote><em>Does IMAP server support UTF-8 in search queries?</em> ';

            if ($imap_client->validSearchCharset('UTF-8')) {
                $ret .= '<span style="color:green">YES</span>';
            } else {
                $ret .= '<span style="color:red">NO</span>';
            }

            $ret .= '</blockquote>';

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
                // Ignore lack of ID capability.
            }

            // @todo IMAP Charset Search Support
        } else {
            $ret .= '<strong>Checking for the UIDL capability:</strong> ';

            if ($imap_client->queryCapability('UIDL')) {
                $ret .= '<span style="color:green">SUCCESS</span><p />';
            } else {
                return $this->_errorMsg(new Exception('The POP3 server does not support the *REQUIRED* UIDL capability.'));
            }
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
        return '<span style="color:red">ERROR</span> - The server returned the following error message:' .
            '<pre>' . $e->getMessage() . '</pre><p />';
    }

}
