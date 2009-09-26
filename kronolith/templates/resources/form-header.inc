<script type="text/javascript">

function setAction(action, cid)
{
    $('actionID').value = action;
    $('cid').value = cid;
    document.resourcesForm.submit();
    return false;
}

</script>
<form name="resourcesForm" method="post" action="<?php echo Horde::selfUrl(false)?>">
<input type="hidden" id="actionID" name="actionID" value="view" />
<input type="hidden" id="cid" name="cid" />
