<h1><?php echo $title ?></h1>

<ul class="notices">
 <li>
  <?php echo Horde::img('alerts/warning.png') . sprintf(_("User %s would like to remain private."), $user) ?>
 </li>
<?php
if ($registry->hasMethod('letter/compose')) {
    echo '<li>' . Horde::img('letter.png')
                    . sprintf(_("You can still send a private message to user %s."), $user)
                    . ' <a href="' . $registry->callByPackage('letter', 'compose', array(array('user_to' => $user)))  . '">' . _("Click here") . '</a>'
                     . '</li>';
}
?>
</ul>
