<?php
Horde::addScriptFile('popup.js', 'horde', true);
if ($vfs = Agora::getVFS() instanceof PEAR_Error) {
    Horde::fatal($vfs, __FILE__, __LINE__);
}
if ($GLOBALS['conf']['avatar']['enable_gallery'] && $vfs->isFolder(Agora::AVATAR_PATH, 'gallery')): ?>
<p>
 <?php echo _("Choose from a gallery of avatars: ") ?><input class="button" type="button" value="Click to Show Gallery" onclick="popup('<?php echo Horde::applicationUrl('avatars/gallery.php') ?>', 320, 320);">
</p>
<?php endif; ?>
