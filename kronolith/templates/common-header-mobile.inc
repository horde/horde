<!DOCTYPE html>
<html>
<head>
<?php
$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}?>
    <title><?php echo htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
    <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
    <script type="text/javascript" src="/horde/js/date/en-US.js"></script>
    <script type="text/javascript" src="/horde/js/date/date.js"></script>
    <?php Horde::outputInlineScript(); ?>
</head>