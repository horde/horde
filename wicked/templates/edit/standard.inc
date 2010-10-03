<script type="text/javascript">
function preview()
{
    document.editform.action = 'preview.php';
    document.editform.target = '_blank';
    document.editform.submit();

    document.editform.action = '<?php echo Wicked::url('EditPage') ?>';
    document.editform.target = '';
}
</script>

<form method="post" name="editform" action="<?php echo Wicked::url('EditPage') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="EditPage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($page->pageName()) ?>" />

<h1 class="header">
 <span class="smallheader rightFloat"><?php printf(_("Last Modified %s by %s"), $page->formatVersionCreated(), $page->author()) ?></span>
 <?php echo _("Edit Page") . ': ' . Horde::link($page->pageUrl()) . htmlspecialchars($page->pageName()) . '</a>'; if ($page->isLocked()) echo Horde::img('locked.png', _("Locked")) ?>
</h1>

<div class="control">
 <input type="submit" value="<?php echo _("Save") ?>" class="button" />
 <input type="button" value="<?php echo _("Preview") ?>" class="button" onclick="preview()" />
 <a class="button" href="<?php echo $page->pageUrl()->add('actionID', 'unlock') ?>"><?php echo _("Cancel") ?></a>
 <?php if (!empty($GLOBALS['conf']['wicked']['require_change_log'])) echo Horde::img('required.png', _("Changelog is required")) ?>
 <strong><?php echo _("Change log") . ': ' ?></strong><input type="text" name="changelog" size="50" />
 <input type="checkbox" name="minor" id="minor" checked="checked" /><label for="minor"> <?php echo _("Minor Change") ?></label>
</div>

<?php
if (!empty($GLOBALS['conf']['wicked']['captcha']) && !$GLOBALS['registry']->getAuth()):
    require_once 'Text/Figlet.php';
    $figlet = new Text_Figlet();
    $result = $figlet->loadFont($GLOBALS['conf']['wicked']['figlet_font']);
    if (!is_a($result, 'PEAR_Error')):
?>
<div class="control">
 <?php echo _("Spam Protection - Enter the letters below:") ?>
 <pre><?php echo $figlet->lineEcho(Wicked::getCAPTCHA(true)) ?></pre>
 <input name="wicked_captcha" />
</div>
<?php endif; endif; ?>

<div style="padding:8px">
 <textarea class="fixed" style="width:100%" name="page_text" rows="29" cols="100"><?php echo @htmlspecialchars($page_text, ENT_COMPAT, 'UTF-8') ?></textarea>
</div>

</form>
