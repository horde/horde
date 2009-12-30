<?php
echo Horde::link(
    Util::addParameter(
        Horde::applicationUrl("edit/context.php"), "context", $context));
echo $context;
echo "</a><br />";