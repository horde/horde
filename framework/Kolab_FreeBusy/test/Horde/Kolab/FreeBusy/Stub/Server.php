<?php

class Horde_Kolab_FreeBusy_Stub_Server extends Horde_Kolab_Server_Composite
{
    private $_data = array(
        'dn=test' => array(
            'uid' => 'foo@example.com',
            'mail' => 'mail@example.org',
            'cn' => 'Test Test',
            'alias' => 'alias@example.com',
        ),
        'dn=x' => array(
            'uid' => 'y',
            'mail' => 'z@example.org',
            'cn' => 'Mister X',
        ),
        'dn=new' => array(
            'uid' => 'F',
            'mail' => 'new@example.org',
            'cn' => 'Misses New',
        ),
        'dn=remote' => array(
            'uid' => 'remote@example.com',
            'mail' => 'remote@example.org',
            'cn' => 'Remote',
            'server' => array(
                'freebusy' => 'https://example.com/freebusy',
            ),
        ),
        'dn=test2' => array(
            'uid' => 'test@example.com',
            'mail' => 'test@example.com',
            'cn' => 'Test Test',
        ),
    );

    public function __construct()
    {
    }

    public function __get($name)
    {
        return $this;
    }

    public function fetch($guid, $type)
    {
        return new Horde_Kolab_FreeBusy_Stub_Object(
            $this->_data[$guid]
        );
    }

    public function searchGuidForUidOrMailOrAlias($id)
    {
        foreach ($this->_data as $key => $user) {
            if (isset($user['uid']) && $user['uid'] == $id) {
                return $key;
            }
            if (isset($user['mail']) && $user['mail'] == $id) {
                return $key;
            }
            if (isset($user['alias']) && $user['alias'] == $id) {
                return $key;
            }
        }
        return false;
    }

    public function searchGuidForUidOrMail($id)
    {
        foreach ($this->_data as $key => $user) {
            if (isset($user['uid']) && $user['uid'] == $id) {
                return $key;
            }
            if (isset($user['mail']) && $user['mail'] == $id) {
                return $key;
            }
        }
        return false;
    }

    public function searchGuidForMail($id)
    {
        foreach ($this->_data as $key => $user) {
            if (isset($user['mail']) && $user['mail'] == $id) {
                return $key;
            }
        }
        return false;
    }
}