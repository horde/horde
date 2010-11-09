<?php

$imp_view = IMP::getViewMode();

/* Do this include here so that the generated script can be added with any
 * other scripts already added. */
switch ($imp_view) {
case 'dimp':
    include IMP_TEMPLATES . '/dimp/javascript_defs_dimp.php';
    break;

case 'mimp':
    // Nothing
    break;

default:
    include IMP_TEMPLATES . '/imp/javascript_defs.php';
    break;
}

if (isset($GLOBALS['language'])) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php
echo (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '">' : '<html>') . '<head>';

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();

switch ($imp_view) {
case 'dimp':
    Horde_Themes::includeStylesheetFiles(array(
        'sub' => 'dimp'
    ));
    if (!in_array(basename($_SERVER['PHP_SELF']), array('compose-dimp.php', 'index-dimp.php', 'message-dimp.php'))) {
        Horde::includeScriptFiles();
        Horde::outputInlineScript();
    }
    break;

case 'mimp':
    Horde_Themes::includeStylesheetFiles(array(
        'nohorde' => true,
        'sub' => 'mimp',
        'subonly' => true
    ));
    break;

default:
    Horde_Themes::includeStylesheetFiles();
    Horde::includeScriptFiles();
    Horde::outputInlineScript();
    break;
}
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php if ($imp_view == 'mimp'): ?>
</head>
<body>
<?php elseif ($imp_view == 'dimp'): ?>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
</head>
<?php else: ?>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php if ($mailbox_url = Horde_Util::nonInputVar('mailbox_url')): ?>
<link href="<?php echo $mailbox_url ?>" rel="Up" />
<?php elseif (!Horde_Util::nonInputVar('login_page')): ?>
<link href="<?php echo Horde::url('mailbox.php')->add('mailbox', 'INBOX') ?>" rel="Top" />
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
<?php endif ?>
</head>

<body>
<?php endif; ?>
