<?php $nav_audio = $prefs->getValue('nav_audio') ?>
<p><?php echo _("Play a sound on new mail? Although most browsers support embedded sound files, some may require a plugin.") ?></p>
<ul class="sound-list">
 <li><label><input type="radio" name="nav_audio" value=""<?php if (!$nav_audio) echo ' checked="checked"' ?> /> <?php echo _("No Sound") ?></label></li>
<?php foreach (glob($registry->get('themesfs') . '/graphics/audio/*.wav') as $sound): $sound = basename($sound); ?>
 <li>
  <label>
   <input type="radio" name="nav_audio" value="<?php echo htmlspecialchars($sound) ?>"<?php if ($nav_audio == $sound) echo ' checked="checked"' ?> />
   <?php echo htmlspecialchars($sound) ?>
  </label>
  <embed autostart="false" src="<?php echo $registry->getImageDir() . '/audio/' . htmlspecialchars($sound) ?>" />
 </li>
<?php endforeach; ?>
</ul>
