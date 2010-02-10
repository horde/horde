<?php
    if (!$selected = @unserialize($prefs->getValue($pref))) {
        $selected = array();
    }

    if (empty($_prefs[$pref]['enum'])) {
        $_prefs[$pref]['enum'] = Horde_Util::nonInputVar($pref . '_options');
    }
?>
<div>
 <?php echo Horde::label($pref, $_prefs[$pref]['desc']) ?>
 <?php echo $helplink ?>
</div>
<div>
 <select id="<?php echo $pref ?>" multiple="multiple" size="<?php echo min(4, count($_prefs[$pref]['enum'])) ?>" name="<?php echo $pref ?>[]">
<?php foreach ($_prefs[$pref]['enum'] as $key => $val): ?>
  <option<?php echo in_array($key, $selected) ? ' selected="selected" ' : '' ?> value="<?php echo htmlspecialchars($key) ?>"><?php echo htmlspecialchars($val) ?></option>
<?php endforeach ?>
 </select>
</div>
