<?php

class IMP_Stub_Identity
{
    private $id;

    public function getMatchingIdentity($mail)
    {
        if ($mail == 'test@example.org') {
            return 'test';
        }
    }

    public function setDefault($id)
    {
        if ($id != 'test' && $id != 'other') {
            throw new Exception("Unexpected default $id!");
        }
        $this->_id = $id;
    }

    public function getFromAddress()
    {
        return 'test@example.org';
    }

    public function getValue($value)
    {
        switch ($value) {
        case 'fullname':
            return 'Mr. Test';
        case 'replyto_addr':
            switch ($this->_id) {
            case 'test':
                return 'test@example.org';
            case 'other':
                return 'reply@example.org';
            }
        }
    }
}