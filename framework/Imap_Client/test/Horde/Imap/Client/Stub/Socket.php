<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Stub for testing the IMAP Socket library.
 * Needed because we need to access protected methods.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Stub_Socket extends Horde_Imap_Client_Socket
{
    public $fetch_results;

    public function getThreadSort($data)
    {
        return new Horde_Imap_Client_Data_Thread($this->doServerResponse($this->_pipeline(), $data)->data['threadparse'], 'uid');
    }

    public function parseNamespace($data)
    {
        return $this->doServerResponse($this->_pipeline(), $data)->data['namespace'];
    }

    public function parseACL($data)
    {
        return $this->doServerResponse($this->_pipeline(), $data)->data['getacl'];
    }

    public function parseMyACLRights($data)
    {
        return $this->doServerResponse($this->_pipeline(), $data)->data['myrights'];
    }

    public function parseListRights($data)
    {
        return $this->doServerResponse($this->_pipeline(), $data)->data['listaclrights'];
    }

    /**
     * @param array $data  Options:
     *   - results: (Horde_Imap_Client_Fetch_Results)
     */
    public function parseFetch($data, array $opts = array())
    {
        $pipeline = $this->_pipeline();
        if (isset($opts['results'])) {
            $pipeline->fetch = $opts['results'];
        }
        $pipeline->data['modseqs_nouid'] = array();

        return $this->doServerResponse($pipeline, $data);
    }

    public function doServerResponse($pipeline, $data)
    {
        $server = Horde_Imap_Client_Interaction_Server::create(
            new Horde_Imap_Client_Tokenize($data)
        );
        $this->_serverResponse($pipeline, $server);
        return $pipeline;
    }

    public function doResponseCode($data)
    {
        $server = Horde_Imap_Client_Interaction_Server::create(
            new Horde_Imap_Client_Tokenize($data)
        );
        $this->_responseCode($this->_pipeline(), $server);
    }

    public function pipeline($cmd = null)
    {
        return $this->_pipeline($cmd);
    }

    public function fetch($mailbox, $query, array $options = array())
    {
        return $this->fetch_results;
    }

}
