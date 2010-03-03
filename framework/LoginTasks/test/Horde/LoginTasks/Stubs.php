<?php

class Horde_LoginTasks_Stub_Prefs
extends Horde_Prefs
{
    private $_storage = array();

    public function __construct()
    {
    }

    public function setValue($pref, $val, $convert = true)
    {
        $this->_storage[$pref] = $val;
    }

    public function getValue($pref, $convert = true)
    {
        return isset($this->_storage[$pref]) ? $this->_storage[$pref] : null;
    }
}

class Horde_LoginTasks_Stub_Task
extends Horde_LoginTasks_Task
{
    static public $executed;

    public $interval = Horde_LoginTasks::EVERY;

    public $display = Horde_LoginTasks::DISPLAY_NONE;

    public $priority = Horde_LoginTasks::PRIORITY_NORMAL;

    public function execute()
    {
        Horde_LoginTasks_Stub_Task::$executed[] = get_class($this);
    }
}

class Horde_LoginTasks_Stub_TaskTwo
extends Horde_LoginTasks_Stub_Task
{
}

class Horde_LoginTasks_Stub_Confirm
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmTwo
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmThree
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_YES;
}

class Horde_LoginTasks_Stub_ConfirmNo
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_CONFIRM_NO;
}

class Horde_LoginTasks_Stub_Day
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::DAILY;
}

class Horde_LoginTasks_Stub_First
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::FIRST_LOGIN;
}

class Horde_LoginTasks_Stub_High
extends Horde_LoginTasks_Stub_Task
{
    public $priority = Horde_LoginTasks::PRIORITY_HIGH;
}

class Horde_LoginTasks_Stub_Month
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::MONTHLY;
}

class Horde_LoginTasks_Stub_Notice
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_NOTICE;
}

class Horde_LoginTasks_Stub_NoticeTwo
extends Horde_LoginTasks_Stub_Task
{
    public $display = Horde_LoginTasks::DISPLAY_NOTICE;
}

class Horde_LoginTasks_Stub_Once
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::ONCE;
}

class Horde_LoginTasks_Stub_Week
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::WEEKLY;
}

class Horde_LoginTasks_Stub_Year
extends Horde_LoginTasks_Stub_Task
{
    public $interval = Horde_LoginTasks::YEARLY;
}