<div id="login" data-role="page" data-theme="a">
 <div data-role="header" data-nobackbtn="true">
  <h1><?php echo $this->h($this->title) ?></h1>
 </div>

 <div data-role="content">
  <form action="<?php echo $this->loginurl ?>" method="post" data-ajax="false">
   <input type="hidden" name="anchor_string" value="<?php echo $this->h($this->anchor) ?>" />
   <input type="hidden" name="app" value="<?php echo $this->h($this->app) ?>" />
   <input type="hidden" name="url" value="<?php echo $this->h($this->url) ?>" />
   <input type="hidden" id="horde-login-post" name="login_post" />

   <fieldset>
<?php foreach ($this->loginparams_auth as $key => $val): ?>
    <div data-role="fieldcontain">
     <label for="<?php echo $key ?>"><?php echo $val['label'] ?></label>
     <input id="<?php echo $key ?>" name="<?php echo $key ?>" type="<?php echo $val['type'] ?>" value="<?php echo isset($val['value']) ? $val['value'] : '' ?>" />
    </div>
<?php endforeach; ?>

    <div data-role="collapsible" data-content-theme="a">
     <h3><?php echo _("Other Options") ?></h3>
<?php foreach ($this->loginparams_other as $key => $val): ?>
<?php if ($val['type'] == 'hidden'): ?>
     <input type="hidden" name="<?php echo $key ?>" value="<?php echo isset($val['value']) ? $val['value'] : '' ?>" />
<?php elseif (in_array($val['type'], array('password', 'text'))): ?>
     <div data-role="fieldcontain">
      <label for="<?php echo $key ?>"><?php echo $val['label'] ?></label>
      <input id="<?php echo $key ?>" name="<?php echo $key ?>" type="<?php echo $val['type'] ?>" value="<?php echo isset($val['value']) ? $val['value'] : '' ?>" />
     </div>
<?php elseif ($val['type'] == 'select'): ?>
     <div data-role="fieldcontain">
      <label for="<?php echo $key ?>"><?php echo $val['label'] ?></label>
      <select id="<?php echo $key ?>" name="<?php echo $key ?>">
<?php foreach ($val['value'] as $k2 => $v2): ?>
<?php if (is_null($v2)): ?>
       <option value="" disabled="disabled">- - - - - - - - - -</option>
<?php else: ?>
       <option value="<?php echo $k2 ?>"<?php echo empty($v2['selected']) ? '' : ' selected="selected"' ?>><?php echo $v2['name'] ?></option>
<?php endif; ?>
<?php endforeach; ?>
      </select>
     </div>
<?php endif; ?>
<?php endforeach; ?>
   </div>
  </fieldset>

   <fieldset data-role="controlgroup">
    <input type="submit" name="login_button" value="<?php echo $this->h($this->title) ?>" />
   </fieldset>
  </form>
 </div>
</div>
