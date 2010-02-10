<div>
 <?php echo Horde::label($pref, $_prefs[$pref]['desc']) ?> <?php echo $helplink ?>
</div>
<div class="fixed">
 <textarea id="<?php echo $pref ?>" name="<?php echo $pref ?>" rows="4" cols="80" class="fixed"><?php echo htmlspecialchars($prefs->getValue($pref)) ?></textarea>
</div>
