<?php

$prefs = $this->getMockBuilder('Horde_Prefs')
    ->disableOriginalConstructor()
    ->getMock();
$prefs->method('getValue')->will($this->returnValueMap(array(
    array('from_addr', 'user@example.com'),
    array('fullname', 'User Name'),
)));
$factory = $this->getMockBuilder('Horde_Core_Factory_Identity')
    ->disableOriginalConstructor()
    ->getMock();
$factory->method('create')->willReturn(new Horde_Prefs_Identity(
    array('prefs' => $prefs, 'user' => 'username')
));

return array(
    new Kronolith_Attendee(array(
        'email' => 'juergen@example.com',
        'role' => Kronolith::PART_REQUIRED,
        'response' => Kronolith::RESPONSE_NONE,
        'name' => 'Jürgen Doe'
    )),
    new Kronolith_Attendee(array(
        'role' => Kronolith::PART_OPTIONAL,
        'response' => Kronolith::RESPONSE_ACCEPTED,
        'name' => 'Jane Doe'
    )),
    new Kronolith_Attendee(array(
        'email' => 'jack@example.com',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_DECLINED,
        'name' => 'Jack Doe'
    )),
    new Kronolith_Attendee(array(
        'email' => 'jenny@example.com',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE
    )),
    new Kronolith_Attendee(array(
        'user' => 'username',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE,
        'identities' => $factory
    )),
    new Kronolith_Attendee(array(
        'user' => 'username2',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE,
        'name' => 'Another User'
    ))
);
