<div>
 <a href="<?php echo isset($_prefs[$pref]['url']) ? Horde::applicationUrl($_prefs[$pref]['url']) : $_prefs[$pref]['xurl'] ?>"<?php echo !empty($_prefs[$pref]['target']) ? ' target="' . htmlspecialchars($_prefs[$pref]['target']) . '"' : '' ?>>
 <?php if (isset($_prefs[$pref]['img'])) echo Horde::img($_prefs[$pref]['img'], $_prefs[$pref]['desc'], 'hspace="3"') ?><strong><?php echo $_prefs[$pref]['desc'] ?></strong></a>
 <?php echo $helplink ?>
</div>
