<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 */
?>
<div class="box" style="float: right;">
<p>
<?php
echo _("Domain Categories") . ' ';
if ($GLOBALS['registry']->getAuth() && (!$prefs->isLocked('categories') || !$prefs->isLocked('category_colors'))) {
    $categoryUrl = Horde_Util::addParameter(Horde::url($registry->get('webroot', 'horde') . '/services/prefs.php'),
                                        array('app' => 'horde', 'group' => 'categories'));
    echo Horde::link($categoryUrl, _("Edit domain groups and colors"), 'widget', '_blank') .
         Horde::img('colorpicker.png', _("Edit categories and colors"), array('align' => 'absmiddle')) . '</a>';
}
?>
</p>
<p>
<ul>
    <?php
    foreach ($categories as $category) {
        $color = isset($colors[$category]) ? $colors[$category] : '#FFFFFF';
        $fgcolor = isset($fgcolors[$category]) ? $fgcolors[$category] : '#000000';
    ?>
        <li style="background-color:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>">
            <input type="checkbox" id="dgroup-<?php echo $category; ?>" onclick="javascript:groupToggle('<?php echo $category; ?>');" />
            <?php echo $category; ?>
        </li>
    <?php
    }
    ?>
</ul>
</p>
</div>

<table class="striped">
<thead>
<tr>
<th><?php echo _("Actions")?></th>
<?php
foreach ($fields as $field) {
    echo '<th>' . $field['name'] . '</th>';
}
?>
</tr>
</thead>
<tbody>

