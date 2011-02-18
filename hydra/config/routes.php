<?php

// Admin index
$mapper->connect('admin', array('controller' => 'admin'));

// Default route for serving content
$mapper->connect('page', ':page', array('controller' => 'page'));
