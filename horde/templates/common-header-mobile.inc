<?php

header('Content-type: text/html; charset=UTF-8');
header('Vary: Accept-Language');

$page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

/* CSS Files. */
$page_output->addStylesheet(
    $GLOBALS['registry']->get('jsfs', 'horde') . '/jquery.mobile/jquery.mobile.min.css',
    $GLOBALS['registry']->get('jsuri', 'horde') . '/jquery.mobile/jquery.mobile.min.css'
);
$page_output->addThemeStylesheet('mobile.css');

/* JS Files. */
$page_output->hsf->prototypejs = false;
$page_output->addScriptFile('jquery.mobile/jquery.min.js', 'horde');
$page_output->addScriptFile('horde-jquery.js', 'horde');
$page_output->addScriptFile('mobile.js', 'horde');

$GLOBALS['registry']->callAppMethod($GLOBALS['registry']->getApp(), 'mobileInitCallback');

$page_output->addScriptFile('jquery.mobile/jquery.mobile.min.js', 'horde');

?>
<!DOCTYPE html>
<html>
 <head>
  <title><?php echo htmlspecialchars(Horde_Util::nonInputVar('title')) ?></title>
  <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">
<?php

$page_output->includeStylesheetFiles(array(
    'nobase' => true,
    'nocache' => true,
));
$page_output->includeFavicon();
$page_output->includeScriptFiles();
$page_output->outputInlineScript();
