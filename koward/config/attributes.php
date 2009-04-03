<?php

$attributes['givenName'] = array(
    'label' => _("First Name"),
    'type' => 'text',
    'required' => true,
    'params' => array('regex' => '', 'size' => 40, 'maxlength' => 255)
);
$attributes['sn'] = array(
    'label' => _("Last Name"),
    'type' => 'text',
    'required' => true,
    'params' => array('regex' => '', 'size' => 40, 'maxlength' => 255)
);
$attributes['mail'] = array(
    'label' => _("Mail address"),
    'type' => 'text',
    'required' => true,
    'params' => array('regex' => '', 'size' => 40, 'maxlength' => 255)
);
$attributes['uid'] = array(
    'label' => _("User ID"),
    'type' => 'text',
    'required' => true,
    'params' => array('regex' => '', 'size' => 40, 'maxlength' => 255)
);
