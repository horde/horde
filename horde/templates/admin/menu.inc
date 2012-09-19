<?php
$page_output->sidebar = false;
echo $injector->getInstance('Horde_View_Topbar')->render();
$notification->notify(array('listeners' => 'status'));
