<input type="hidden" name="fbactionID" id="fbactionID" />

<?php if ($this->have_session): ?>
<div><?php echo $this->user_name ?></div>
<div><img src="<?php echo $this->user_pic_url ?>" /></div>
<div>
 <br />
 <?php echo _("Logged in to Facebook") ?>
 <br />
 <input type="submit" value="<?php echo _("Logout") ?>" onclick="document.prefs.fbactionID.value='revokeApplication'; return true" />
</div>

<div>
 <br />
<?php if ($this->have_publish): ?>
 <?php echo _("Publish enabled.") ?>
 <br />
 <input type="submit" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokePublish'; return true" />
<?php else: ?>
 <?php printf(_("%s cannot set your status messages or publish other content to Facebook."), $this->app_name) ?>
 <?php echo _("Authorize Publish") ?>:
 <br />
 <a class="horde-button" href="<?php echo $this->publish_url ?>"><?php echo _("Authorize") ?></a>
<?php endif; ?>
</div>

<div>
 <br />
<?php if ($this->have_read): ?>
 <?php echo _("Read enabled.") ?>
 <br />
 <input type="submit" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokeRead'; return true" />
<?php else: ?>
 <?php printf(_("%s cannot read your stream messages and various other Facebook data items."), $this->app_name) ?>
 <?php echo _("Authorize Read") ?>:
 <br />
 <a class="horde-button" href="<?php echo $this->read_url ?>"><?php echo _("Authorize") ?></a>
<?php endif; ?>
</div>

<div>
 <br />
<?php if ($this->have_friends): ?>
 <?php echo _("Friends enabled.") ?>
 <br />
 <input type="submit" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokeFriends'; return true" />
<?php else: ?>
 <?php printf(_("%s cannot read information about your Facebook friends."), $this->app_name) ?>
 <?php echo _("Authorize Access to Friends Data") ?>:
 <br />
 <a class="horde-button" href="<?php echo $this->friends_url ?>"><?php echo _("Authorize") ?></a>
<?php endif; ?>
</div>

<?php else: ?>
<div>
 <?php printf(_("Could not find authorization for %s to interact with your Facebook account."), $this->user_name) ?>
</div>
<br />
<div>
 <?php printf(_("Login to Facebook and authorize %s"), $this->app_name) ?>:
 <a href="<?php echo $this->authUrl ?>" class="horde-default"><?php echo _("Authorize") ?></a>
</div>
<?php endif; ?>
