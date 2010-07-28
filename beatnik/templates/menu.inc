<?php
/*
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$accesskey = $prefs->getValue('widget_accesskey') ? Horde::getAccessKey(_("Select _Domain")) : '';
$menu_view = $prefs->getValue('menu_view');
?>
<div id="menu">
<span style="float:right">
<?php
    $link = Horde::link('#', _("Select Domain"), '', '', 'domainSubmit(true); return false;');
    sprintf('<ul><li>%s%s<br />%s</a></li></ul>', $link, Horde::img('tree/folderopen.png'), ($menu_view != 'icon') ? Horde::highlightAccessKey(_("Select _Domain"), $accesskey) : '');
?>
</span>
<span style="float:right">
    <form action="<?php echo Horde::applicationUrl('viewzone.php') ?>" method="get" name="menu">
        <?php Horde_Util::pformInput(); ?>
        <label for="domain" accesskey="<?php echo $accesskey ?>">
            <select id="domainSelector" name="curdomain"
                onchange="domainSubmit()">
                <?php
                    foreach ($beatnik->domains as $domain) {
                        echo '<option value="' . $domain['zonename'] . '"';
                        if ($domain['zonename'] == $_SESSION['beatnik']['curdomain']['zonename']) {
                            echo " selected";
                        }
                        echo '>' . $domain['zonename'] . "</option>\n";
                    }
                ?>
            </select>
        </label>
    </form>
</span>
<?php echo Beatnik::getMenu('string') ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
