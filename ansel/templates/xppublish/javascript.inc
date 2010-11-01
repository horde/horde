<div id="content"></div>
<script>

function publish()
{
    var xml = window.external.Property('TransferManifest');
    var files = xml.selectNodes('transfermanifest/filelist/file');

    for (i = 0; i < files.length; i++) {
        var postTag = xml.createNode(1, 'post', '');
        postTag.setAttribute('href', '<?php echo Horde::url('xppublish.php?1=1', true) ?>&gallery=' + folder.gallery.value);
        postTag.setAttribute('name', 'imagefile');

        var dataTag = xml.createNode(1, 'formdata', '');
        dataTag.setAttribute('name', '<?php echo addslashes(session_name()) ?>');
        dataTag.text = '<?php echo addslashes(session_id()) ?>';
        postTag.appendChild(dataTag);

        var dataTag = xml.createNode(1, 'formdata', '');
        dataTag.setAttribute('name', 'cmd');
        dataTag.text = 'add';
        postTag.appendChild(dataTag);

        var dataTag = xml.createNode(1, 'formdata', '');
        dataTag.setAttribute('name', 'action');
        dataTag.text = 'SAVE';
        postTag.appendChild(dataTag);

        files.item(i).appendChild(postTag);
    }

    var uploadTag = xml.createNode(1, 'uploadinfo', '');
    var htmluiTag = xml.createNode(1, 'htmlui', '');
    htmluiTag.text = '<?php echo Horde::url('view.php?view=Gallery', true) ?>&gallery=' + folder.gallery.value;
    uploadTag.appendChild(htmluiTag);

    xml.documentElement.appendChild(uploadTag);

    window.external.Property('TransferManifest') = xml;
    window.external.SetWizardButtons(true, true, true);
    content.innerHtml = xml;
    window.external.FinalNext();
}

function OnBack()
{
    <?php echo $PUBLISH_ONBACK ?>
    window.external.SetWizardButtons(false, true, false);
}

function OnNext()
{
    <?php echo $PUBLISH_ONNEXT ?>
}

function OnCancel()
{
    content.innerHtml += '<br />OnCancel';
}

function window.onload()
{
    window.external.SetHeaderText('<?php echo addslashes($registry->get('name')) . ' :: ' . _("Photo Upload") ?>', '<?php echo addslashes(sprintf(_("Upload Photos to %s"), $registry->get('name'))) ?>');
    window.external.SetWizardButtons(<?php echo $PUBLISH_BUTTONS ?>);
}

<?php echo $PUBLISH_CMD ?>

</script>
