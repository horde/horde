<?php
    $nav_audio = $prefs->getValue('nav_audio')
?>
<div>
 <p><?php echo _("Play a sound on new mail? Although most browsers support embedded sound files, some may require a plugin.") ?></p>
 <ul class="sound-list">
  <li>
   <label>
    <input type="radio" name="nav_audio" value=""<?php if (!$nav_audio) echo ' checked="checked"' ?> />
    <?php echo _("No Sound") ?>
   </label>
  </li>
<?php foreach (Horde_Themes::soundList() as $key => $val): ?>
  <li>
   <label>
    <input type="radio" name="nav_audio" value="<?php echo htmlspecialchars($key) ?>"<?php if ($nav_audio == $key) echo ' checked="checked"' ?> />
    <?php echo htmlspecialchars($key) ?>
   </label>
   <embed autostart="false" src="<?php echo htmlspecialchars($val->uri) ?>" />
  </li>
<?php endforeach; ?>
 </ul>
</div>
