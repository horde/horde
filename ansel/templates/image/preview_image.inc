<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?></h1>
<?php
$base_url = Horde::applicationUrl('image.php')->add(
        array_merge(
            array('gallery' => $gallery_id,
                  'image' => $image_id,
                  'page' => $page,
                  'watermark' => $watermark,
                  'whalign' => $watermark_halign,
                  'wvalign' => $watermark_valign,
                  'font' => $watermark_font),
            $date));

$edit_url = $base_url->copy()->add('actionID', 'editimage');
$save_url = $base_url->copy()->add('actionID', $action);
$image_url = $base_url->copy()->add(array('actionID' => 'image' . $action,
                                          'view' => 'screen'));
?>
<div class="control anselActions" style="text-align:center;">
<?php
if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    echo $save_url->link(array('title' => _("Save Change")))
             . Horde::img('save.png') . ' ' . _("Save Change") . '</a> ';
}
if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    echo $edit_url->link(array('title' => _("Cancel Change")))
             . Horde::img('delete.png') . ' ' . _("Cancel Change") . '</a>';
}
?>
</div>
<br />
<div align="center">
<?php echo '<img src="' . $image_url . '" alt="[preview]" />' ?>
<br />
<?php $caption = $image->caption ? $image->caption : $image->filename; echo $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
