<?php

if (isset($GLOBALS['language'])) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php
echo (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '">' : '<html>') . '<head>';

$page_title = $GLOBALS['registry']->get('name');
if (!empty(IMP::$title)) {
    $page_title .= ' :: ' . IMP::$title;
}

$page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

$page_output->outputMetaTags();

switch ($view_mode) {
case Horde_Registry::VIEW_MINIMAL:
    $page_output->includeStylesheetFiles(array(
        'nohorde' => true,
        'sub' => 'mimp',
        'subonly' => true
    ));
    break;

default:
    $page_output->includeStylesheetFiles();
    $page_output->includeScriptFiles();
    $page_output->includeFavicon();
    $page_output->outputInlineScript();
    break;
}
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php if ($view_mode == Horde_Registry::VIEW_BASIC): ?>
<?php if ($mailbox_url = Horde_Util::nonInputVar('mailbox_url')): ?>
<link href="<?php echo $mailbox_url ?>" rel="Up" />
<?php elseif (!Horde_Util::nonInputVar('login_page')): ?>
<link href="<?php echo IMP_Mailbox::get('INBOX')->url('mailbox.php') ?>" rel="Top" />
<?php endif; ?>
<?php if ($first_url = Horde_Util::nonInputVar('first_url')): ?>
<link href="<?php echo $first_url ?>" rel="First" />
<?php endif; ?>
<?php if ($prev_url = Horde_Util::nonInputVar('prev_url')): ?>
<link href="<?php echo $prev_url ?>" rel="Previous" id="prev" />
<?php endif; ?>
<?php if ($next_url = Horde_Util::nonInputVar('next_url')): ?>
<link href="<?php echo $next_url ?>" rel="Next" id="next" />
<?php endif; ?>
<?php if ($last_url = Horde_Util::nonInputVar('last_url')): ?>
<link href="<?php echo $last_url ?>" rel="Last" />
<?php endif; ?>
<?php if ($rss_url = Horde_Util::nonInputVar('rss_url')): ?>
<link rel="alternate" type="application/rss+xml" href="<?php echo $rss_url ?>" />
<?php endif; ?>
<?php if (!Horde_Util::nonInputVar('login_page')): ?>
<link href="<?php echo Horde::url('search.php') ?>" rel="search" />
<?php endif; ?>
<?php endif; ?>

</head>
<body>
