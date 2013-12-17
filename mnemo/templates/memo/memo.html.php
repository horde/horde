<h1 class="header"><?php echo $this->h($this->title) ?></h1>

<div class="horde-content">
<?php if ($this->modify && $this->passphrase): ?>
 <div class="notePassword">
  <form action="<?php echo $this->url ?>" name="passphrase" method="post">
   <?php echo $this->formInput ?>
   <input type="hidden" name="actionID" value="modify_memo" />
   <input type="hidden" name="memolist" value="<?php echo $this->h($this->listid) ?>" />
   <input type="hidden" name="memo" value="<?php echo $this->h($this->id) ?>" />
   <?php echo Horde::label('mnemo-passphrase', _("_Password")) ?>:
   <input type="password" id="mnemo-passphrase" name="memo_passphrase" />
   <input class="horde-default" type="submit" value="<?php echo _("Decrypt") ?>" />
  </form>
 </div>
<?php else: ?>
 <?php echo $this->javascript ?>
 <form method="post" name="memo" action="<?php echo $this->url ?>">
 <?php echo $this->formInput ?>
 <input type="hidden" name="actionID" value="save_memo" />
 <input type="hidden" name="memo" value="<?php echo $this->h($this->id) ?>" />
 <input type="hidden" name="memolist_original" value="<?php echo $this->h($this->listid) ?>" />
<?php if (count($this->notepads) <= 1): ?>
 <input type="hidden" name="notepad_target" value="<?php echo $this->h($this->listid) ?>" />
<?php endif; ?>
 <p><?php echo Horde::label('mnemo-body', _("Note _Text")) ?>&nbsp;(<?php echo $this->count ?>):</p>
 <textarea name="memo_body" id="mnemo-body" class="fixed" rows="20"><?php echo $this->h($this->body) ?></textarea>
 <?php echo $this->help ?>

 <p class="horde-form-buttons">
  <input type="submit" class="horde-default" value="<?php echo _("Save") ?>" />
<?php if ($this->delete): ?>
  <a class="horde-delete" href="<?php echo $this->delete ?>"><?php echo _("Delete") ?></a>
<?php endif ?>
 </p>
 <table>
<?php if (count($this->notepads) > 1): ?>
  <tr>
   <td class="rightAlign"><?php echo Horde::label('notepad_target', _("Note_pad:")) ?></td>
   <td>
    <select id="notepad_target" name="notepad_target">
<?php foreach ($this->notepads as $notepad): ?>
     <option value="<?php echo $this->h($notepad['id']) ?>"<?php if ($notepad['selected']) echo ' selected="selected"' ?>><?php echo $this->h($notepad['label']) ?></option>
<?php endforeach ?>
    </select>
   </td>
  </tr>
<?php endif; ?>
  <tr>
   <td class="rightAlign"><?php echo Horde::label('memo_tags', _("T_ags:")) ?></td>
   <td>
    <input id="memo_tags" type="text" name="memo_tags" value="<?php echo $this->h($this->tags) ?>" />
    <span id="memo_tags_loading_img" style="display:none;"><?php echo $this->loadingImg ?></span>
   </td>
  </tr>
<?php if ($this->encryption): ?>
<?php if ($this->modify && $this->encrypted && !$this->passphrase): ?>
  <tr>
   <td class="rightAlign"><?php echo Horde::label('memo_encrypt', _("_Encrypt?")) ?></td>
   <td><input type="checkbox" id="memo_encrypt" name="memo_encrypt" checked="checked" /></td>
  </tr>
<?php endif; ?>
  <tr>
   <td class="rightAlign"><?php echo Horde::label('memo_passphrase', _("_Password:")) ?></td>
   <td><input type="password" id="memo_passphrase" name="memo_passphrase" /></td>
  </tr>
  <tr>
   <td class="rightAlign"><?php echo Horde::label('memo_passphrase2', _("_Repeat:")) ?></td>
   <td><input type="password" id="memo_passphrase2" name="memo_passphrase2" /></td>
  </tr>
<?php endif; ?>
 </table>
</form>
<?php endif; ?>
</div>
