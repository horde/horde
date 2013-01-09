<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Stub for testing the IMAP Socket library.
 * Needed because we need to access protected methods.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Stub_Socket extends Horde_Imap_Client_Socket
{
    public $sort_ob;

    public function __construct(array $params = array())
    {
        parent::__construct($params);

        require_once __DIR__ . '/SocketClientSort.php';

        $this->sort_ob = new Horde_Imap_Client_Stub_SocketClientSort($this);
    }

    public function getClientSort($data, $sort)
    {
        $this->_temp['fetch_cache'] = new Horde_Imap_Client_Fetch_Results();
        $this->_temp['fetch_resp'] = new Horde_Imap_Client_Fetch_Results();

        $ids = array();

        foreach (array_filter($data) as $val) {
            $token = new Horde_Imap_Client_Tokenize($val);
            $token->rewind();
            $token->next();
            $id_str = $token->next();
            $token->next();
            $this->_parseFetch($id_str, $token);
            $ids[] = $id_str;
        }

        return $this->sort_ob->clientSortProcess($ids, $this->_temp['fetch_resp'], $sort);
    }

    public function getThreadSort($data)
    {
        $token = new Horde_Imap_Client_Tokenize($data);
        $token->rewind();

        $this->_parseThread($token);

        return new Horde_Imap_Client_Data_Thread($this->_temp['threadparse'], 'uid');
    }

    public function parseNamespace($data)
    {
        $token = new Horde_Imap_Client_Tokenize($data);
        $token->rewind();

        $this->_parseNamespace($token);

        return $this->_temp['namespace'];
    }

    public function parseFetch($data)
    {
        $token = new Horde_Imap_Client_Tokenize($data);
        $token->rewind();
        $token->next();
        $msg_no = $token->next();
        $token->next();

        $this->_temp['fetch_cache'] = new Horde_Imap_Client_Fetch_Results();
        $this->_temp['fetch_resp'] = new Horde_Imap_Client_Fetch_Results();

        $this->_parseFetch($msg_no, $token);

        return $this->_temp['fetch_resp'];
    }

    public function responseCode($data)
    {
        $token = new Horde_Imap_Client_Tokenize($data);
        $server = Horde_Imap_Client_Interaction_Server::create($token);

        $this->_responseCode($server);
    }

}
