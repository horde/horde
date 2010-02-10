<div>
 <input type="checkbox" class="checkbox" id="<?php echo $pref ?>" name="<?php echo $pref ?>"<?php echo $prefs->getValue($pref) ? ' checked="checked"' : '' ?> />
 <?php echo Horde::label($pref, $_prefs[$pref]['desc']) ?>
 <?php echo $helplink ?>
</div>
