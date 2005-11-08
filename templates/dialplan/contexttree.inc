<script language="JavaScript" type="text/javascript">
<!--
var curExten;
function highlightExten(exten)
{
    if (curExten && curExten != exten) {
        deactivatePriority();

        document.getElementById('eBox-' + curExten).className = 'extensionBox';
        document.getElementById('pList-' + curExten).className = 'pList';
    }

    curExten = exten;
    document.getElementById("eBox-" + exten).className = 'extensionBoxHighlight';
    document.getElementById("pList-" + exten).className = 'pListHighlight';
}

var curPrio;
function activatePriority(exten, prio)
{
    if (curExten) {
        deactivatePriority();
    }

    if (exten != curExten) {
        highlightExten(exten);
    }
    curPrio = prio;
    document.getElementById('pButtons-'+exten+'-'+prio).className = 'pButtonsHighlight';
    document.getElementById('pNumber-'+exten+'-'+prio).className = 'pElementHighlight';
    document.getElementById('pApp-'+exten+'-'+prio).className = 'pElementHighlight';
    document.getElementById('pArgs-'+exten+'-'+prio).className = 'pElementHighlight';
}

function deactivatePriority()
{
    if (curPrio) {
        document.getElementById('pButtons-'+curExten+'-'+curPrio).className = 'pButtons';
        document.getElementById('pNumber-'+curExten+'-'+curPrio).className = 'pElement';
        document.getElementById('pApp-'+curExten+'-'+curPrio).className = 'pElement';
        document.getElementById('pArgs-'+curExten+'-'+curPrio).className = 'pElement';
    }
}
-->
</script>

<div class="header">
    <div class="contextHeader">Context: <?php echo $context; ?></div>
</div>

<div id='contextTree'>
<?php $tree->renderTree(true); ?>
<br />
<a href="#top" class="small">Back to Top</a>
</div>
