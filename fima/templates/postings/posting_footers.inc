</table>
<?php if ($pageOb['mode'] == 'list'): ?>
<input type="hidden" name="page" value="<?php echo $pageOb['page'] ?>" />
<input type="hidden" name="actionID" value="" />
</form>

<script type="text/javascript">
<!--

Flags = new Array(<?php
if (isset($flags) && is_array($flags)) {
    for ($i = 0; $i < count($flags); $i++) {
        if ($i > 0) {
            echo ', ';
        }
        echo '"' . $flags[$i] . '"';
    }
}
?>);

//-->
</script>
<?php elseif($pageOb['mode'] == 'edit'): ?>
<script type="text/javascript">
<!--

<?php
$assetindex = '';
$assetresult = '';
if (isset($assetresults) && is_array($assetresults)) {
    $now = mktime();
    $currentassets = array();
    foreach ($postings as $postingId => $posting) {
        if ($posting['asset'] && $posting['date'] <= $now) {
            $currentassets[$posting['asset']] = $posting['amount'];
        }
    }
    foreach ($assetresults as $key => $asset) {
        if (isset($currentassets[$asset['account_id']])) {
            $asset['account_result'] -= $currentassets[$asset['account_id']];
        }
        if ($key > 0) {
            $assetindex .= ', ';
            $assetresult .= ', ';
        }
        $assetindex .= '"' . $asset['account_id'] . '"';
        $assetresult .= '"' . (float)$asset['account_result'] . '"';
    }
}
?>
AssetIndex = new Array(<?php echo $assetindex ?>);
AssetResult = new Array(<?php echo $assetresult ?>);

//-->
</script>
<?php endif; ?>
