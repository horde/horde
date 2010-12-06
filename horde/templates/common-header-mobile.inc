<?php

header('Content-type: text/html; charset=UTF-8');
header('Vary: Accept-Language');

?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars(Horde_Util::nonInputVar('title')) ?></title>
<?php

/* CSS Files. */
$horde_css = $GLOBALS['injector']->getInstance('Horde_Themes_Css');
$horde_css->addStylesheet(
    $GLOBALS['registry']->get('jsfs', 'horde') . '/jquery.mobile/jquery.mobile.min.css',
    $GLOBALS['registry']->get('jsuri', 'horde') . '/jquery.mobile/jquery.mobile.min.css'
);
$horde_css->addThemeStylesheet('mobile.css');
Horde::includeStylesheetFiles(array(
    'nobase' => true
));

/* JS Files. */
$GLOBALS['injector']->getInstance('Horde_Script_Files')->prototypejs = false;
Horde::addScriptFile('jquery.mobile/jquery.min.js', 'horde');
Horde::addScriptFile('horde-jquery.js', 'horde');
Horde::addScriptFile('mobile.js', 'horde');

$GLOBALS['registry']->callAppMethod($GLOBALS['registry']->getApp(), 'mobileInitCallback');

Horde::addScriptFile('jquery.mobile/jquery.mobile.min.js', 'horde');
Horde::includeScriptFiles();
