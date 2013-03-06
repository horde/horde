<?php

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('wicked');
Horde::url('index.php?page=' . $_POST['page_title'], true)->redirect();