<?php if (!$prefs->isLocked('default_identity')): ?>
<?php
    echo Horde::wrapInlineScript(array(
        'IdentitySelect.deleteurl = ' . Horde_Serialize::serialize(strval(Horde::selfUrl(true)->setRaw(true)->add('actionID', 'delete_identity')), Horde_Serialize::JSON)
    ));
?>
<div>
 <p>
  <input id="deleteidentity" type="button" class="button" value="<?php echo _("Delete selected identity") ?>" />
 </p>
</div>
<?php endif; ?>
