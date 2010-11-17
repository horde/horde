<?php

echo '<h1 class="header">' . $title . '</h1>';

foreach ($apps as $app => $name) {
    try {
        $page = $registry->getInitialPage($app);
        echo '<div class="appService">' .
                '<a href="' . $page . '">' .
                Horde::img(Horde_Themes::img($app . '.png', $app)) . ' ' .
                    $name .
                    '</a></div>';
    } catch (Horde_Exception $e) {}
}
