<div>
 <?php echo Horde::label($pref, $_prefs[$pref]['desc']) ?>
 <?php echo $helplink ?>
</div>
<div>
 <input type="text" id="<?php echo $pref ?>" name="<?php echo $pref ?>" size="3" maxlength="3" value="<?php echo htmlspecialchars($prefs->getValue($pref)) ?>" />
</div>
