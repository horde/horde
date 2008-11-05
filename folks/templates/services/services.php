<?php

echo '<h1 class="header">' . $title . '</h1>';

foreach ($apps as $app => $name) {
    $page = $registry->getInitialPage($app);
    if ($page instanceof PEAR_Error) {
        continue;
    }
    echo '<div class="appService">' .
            '<a href="' . $page . '">' . 
            '<img src="' . $registry->getImageDir($app) . '/'.  $app . '.png" /> ' .
                $name .
                '</a></div>';
}