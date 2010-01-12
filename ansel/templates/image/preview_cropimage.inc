<h1 class="header"><?php echo htmlspecialchars($title, ENT_COMPAT, Horde_Nls::getCharset()) ?></h1>
<?php
$base_url = Horde_Util::addParameter('image.php', array_merge(
                                            array('gallery' => $gallery_id,
                                                  'image' => $image_id,
                                                  'page' => $page,
                                                  'params' => $params),
                                            $date));

$edit_url = Horde_Util::addParameter($base_url, 'actionID', 'editimage');
$save_url = Horde_Util::addParameter($base_url, 'actionID', $action);
$image_url = Horde::applicationUrl(Horde_Util::addParameter($base_url, array('actionID' => 'image' . $action,
                                                                       'view' => 'full')));
?>
<div class="control anselActions" style="text-align:center;">
<?php
if ($gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
    echo Horde::link(Horde::applicationUrl($save_url), _("Save Change"))
             . Horde::img('save.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Save Change") . '</a> ';
}
if ($gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
    echo Horde::link(Horde::applicationUrl($edit_url), _("Cancel Change"))
             . Horde::img('delete.png', '', '', $registry->getImageDir('horde')) . ' ' . _("Cancel Change") . '</a>';
}
?>
</div>
<br />
<div align="center">
<?php echo '<img src="' . $image_url . '" alt="[preview]" />'?>
<br />
<?php $caption = $image->caption ? $image->caption : $image->filename; echo Horde_Text_Filter::filter($caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
