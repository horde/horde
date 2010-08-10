<?php

class IMP_Stub_Prefs
{
    public function getValue($pref)
    {
        switch($pref) {
        case 'date_format':
            return '%x';
        case 'twentyFour':
            return true;
        }
    }
}