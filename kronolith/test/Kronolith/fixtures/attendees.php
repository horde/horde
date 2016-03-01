<?php

return array(
    new Kronolith_Attendee(array(
        'email' => 'juergen@example.com',
        'role' => Kronolith::PART_REQUIRED,
        'response' => Kronolith::RESPONSE_NONE,
        'name' => 'Jürgen Doe'
    )),
    new Kronolith_Attendee(array(
        'email' => 'Jane Doe',
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
    ))
);
