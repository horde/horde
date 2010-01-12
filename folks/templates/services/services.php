<?php

echo '<h1 class="header">' . $title . '</h1>';

foreach ($apps as $app => $name) {
    try {
        $page = $registry->getInitialPage($app);
        echo '<div class="appService">' .
                '<a href="' . $page . '">' .
                '<img src="' . $registry->getImageDir($app) . '/'.  $app . '.png" /> ' .
                    $name .
                    '</a></div>';
    } catch (Horde_Exception $e) {}
}
