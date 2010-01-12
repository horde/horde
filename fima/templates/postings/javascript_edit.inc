<script type="text/javascript">
<!--

function updateWday(field)
{
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
    wdays = new Array(
        '<?php echo addslashes(_("Sunday")) ?>',
        '<?php echo addslashes(_("Monday")) ?>',
        '<?php echo addslashes(_("Tuesday")) ?>',
        '<?php echo addslashes(_("Wednesday")) ?>',
        '<?php echo addslashes(_("Thursday")) ?>',
        '<?php echo addslashes(_("Friday")) ?>',
        '<?php echo addslashes(_("Saturday")) ?>'
    );
    spanObj = _get(field + '_wday');
    day = _get(field + '[day]').value;
    month = _get(field + '[month]').value - 1;
    year = _get(field + '[year]').value;

    date = new Date(year, month, day)
    spanObj.innerHTML = '(' + wdays[date.getDay()] + ')';
<?php endif; ?>
}

function _get(id)
{
    return document.getElementById ?
        document.getElementById(id) :
        eval('document.all.' + id);
}

function _getall(fieldname)
{
    return document.getElementsByName ?
        document.getElementsByName(fieldname) :
        eval('document.all.' + fieldname);
}

var amountformat = '<?php echo addslashes($amountfmt) ?>';
var hsign = amountformat.substr(0, 1);
var comma = amountformat.substr(1, 1);

function convertAmountToValue(amount)
{
    hsignx = (hsign == '.') ? '\\.' : hsign;
    commax = (comma == '.') ? '\\.' : comma;
    if (hsign != '.' && comma != '.') {
        amount = amount.replace(/\./g, '');
    }
    amount = amount.replace(new RegExp(hsignx, 'g'), '');
    amount = amount.replace(new RegExp(commax, 'g'), '.');

    try {
        amounteval = eval(amount);
        amount = amounteval;
    } catch (e) {
    }

    val = parseFloat(amount);
    if (isNaN(val)) {
        return 0;
    }
    
    return val;
}

function convertValueToAmount(val)
{
    val = val.toString();
    if (val.indexOf('.') == -1) {
        parts = new Array(val, '00');
    } else {
        parts = val.split('.');
        parts[1] += '00';
    }

    intpart = '';
    i = parts[0].length;
    while (i > 0) {
        if (i - 3 < 0) {
            d = i;
            i = 0;
        } else {
            d = 3;
            i = i - 3;
        }
        part = parts[0].substr(i, d);
        if (part == '-' || intpart == '') {
            intpart = part + intpart;
        } else {
            intpart = part + hsign + intpart;
        }
    }    
    fracpart = parts[1].substr(0, 2);

    return intpart + comma + fracpart;
}

function formatAmount(field)
{
    if (field.value != '') {
        val = convertAmountToValue(field.value);
        field.value = convertValueToAmount(val);
    }
}



var dateformat = '<?php echo addslashes($datefmt) ?>';
var separator = dateformat.match(/[^%a-zA-Z]/);
var datepos = new Array();

function convertDateToStamp(datevalue)
{
    if (separator == '') {
        return;
    }
    
    formatparts = dateformat.split(separator);
    
    datevalue = datevalue.replace(/[^0-9]/g, separator);
    dateparts = datevalue.split(separator);

    datepos = new Array();
    for (i = 0, j = 0, p = 0; i < formatparts.length; i++, j++) {
        if (formatparts[i] == '%Y')        { d = 0;
        } else if (formatparts[i] == '%m') { d = 1;
        } else if (formatparts[i] == '%d') { d = 2;
        } else { continue; }
        
        datepos[d] = i;
        
        if (datevalue.indexOf(separator) == -1) {
          if (d == 0 && datevalue.length <= 4) {
              dateparts[i] = parseInt((new Date()).getFullYear(), 10);
          } else {
              dp = (d == 0 && datevalue.length > 6) ? 4 : 2;
              dateparts[i] = parseInt(datevalue.substr(p, dp), 10);
              p += dp;
          }
        } else if (d == 0 && dateparts.length < 3) {
          dateparts[i] = parseInt((new Date()).getFullYear(), 10);
          j--;
        } else if (dateparts[j]) {
            dateparts[i] = parseInt(dateparts[j], 10);
        } else {
            dateparts[i] = 1;
        }
    }

    if (dateparts[datepos[0]] < 70) {
        dateparts[datepos[0]] += 2000;
    } else if (dateparts[datepos[0]] < 100) {
        dateparts[datepos[0]] += 1900;
    }

    stamp = new Date(dateparts[datepos[0]], dateparts[datepos[1]] - 1, dateparts[datepos[2]]);
    return stamp;
}

function convertStampToDate(stamp)
{
    dateparts = new Array();
    dateparts[datepos[0]] = stamp.getFullYear().toString();
    dateparts[datepos[1]] = (101 + stamp.getMonth()).toString().substr(1, 2);
    dateparts[datepos[2]] = (100 + stamp.getDate()).toString().substr(1, 2);

    return dateparts.join(separator);
}

function formatDate(field)
{
    if (field.value == '') {
        return;
    }
     
    stamp = convertDateToStamp(field.value);
    field.value = convertStampToDate(stamp);
}

function updateDate(field)
{
    if (field.value != '') {
        return;
    }

    if (field.value == '') {
        fields = _getall(field.name);
        for (i = 0; i < fields.length; i++) {
            if (fields[i] == field) {
                if (i > 0) {
                    field.value = fields[i - 1].value;
                }
                break;
            }
        }
    }
    return;
}

function updateAccount(field)
{
    if (!field.options) {
        return;
    }
    
    if (field.options[field.selectedIndex].value == '') {
        if (field.length == 2) {
            field.options[field.length - 1].selected = true;
        } else {
            fields = _getall(field.name);
            for (i = 0; i < fields.length; i++) {
                if (fields[i] == field) {
                    if (i > 0) {
                        field.selectedIndex = fields[i - 1].selectedIndex + (fields[i - 1].options[0].value == '' ? 0 : 1);
                    }
                    break;
                }
            }
        }
    }
}

function updateEo(field)
{
    if (!field.options) {
        return;
    }
    
    eo = (field.options[field.selectedIndex].className.indexOf('eo') != -1);
    accountfields = _getall('account[]');

    for (i = 0; i < accountfields.length; i++) {
        if (accountfields[i] == field) {
            _get('eo' + i).checked = eo;
            break;
        }
    }
}

function updateResult()
{
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
    result = 0;
    amountfields = _getall('amount[]');
    accountfields = _getall('account[]');
    for (i = 0; i < amountfields.length; i++) {
        if (accountfields[i].options[accountfields[i].selectedIndex].className.indexOf('<?php echo FIMA_ACCOUNTTYPE_ASSET ?>') == -1) {
            result += convertAmountToValue(amountfields[i].value) * (<?php if (!$prefs->getValue('expenses_sign')) { ?>(accountfields[i].options[accountfields[i].selectedIndex].className.indexOf('<?php echo FIMA_ACCOUNTTYPE_EXPENSE ?>') != -1) ? -1 : <?php } ?>1);
        }
    }
    
    spanObj = _get('inforesult');
    spanObj.innerHTML = convertValueToAmount(result);
    spanObj.className = ((result >= 0) ? 'positive' : 'negative') + ' postingResult';
<?php endif; ?>
}

var AssetIndex;
var AssetResult;

function updateAssetResult(field)
{
<?php if ($GLOBALS['browser']->hasFeature('dom')): ?>
    amountfields = _getall('amount[]');
    assetfields = _getall('asset[]');
    accountfields = _getall('account[]');
    
    for (i = 0; i < amountfields.length; i++) {
        if (amountfields[i] == field || assetfields[i] == field) {
            break;
        }
    }

    asset = assetfields[i].options[assetfields[i].selectedIndex];    
    if (asset.value == '') {
        assettext = '';
        result = 0;
    } else {
        assettext = asset.text;
        result = 0;
        for (i = 0; i < AssetIndex.length; i++) {
            if (AssetIndex[i] == asset.value) {
                ar = parseFloat(AssetResult[i]);
                if (!isNaN(ar)) {
                    result += ar;
                }
                break;
            }
        }        
        for (i = 0; i < amountfields.length; i++) {
            if (assetfields[i].options[assetfields[i].selectedIndex].value == asset.value) {
                result += convertAmountToValue(amountfields[i].value) * (<?php if (!$prefs->getValue('expenses_sign')) { ?>(accountfields[i].options[accountfields[i].selectedIndex].className.indexOf('<?php echo FIMA_ACCOUNTTYPE_EXPENSE ?>') != -1) ? -1 : <?php } ?>1);
            }
            if (accountfields[i].options[accountfields[i].selectedIndex].value == asset.value) {
                result -= convertAmountToValue(amountfields[i].value);
            }
        }
    }

    spanObj = _get('infoasset');
    spanObj.innerHTML = assettext;
        
    spanObj = _get('infoassetresult');
    spanObj.innerHTML = convertValueToAmount(result);
    spanObj.className = ((result >= 0) ? 'positive' : 'negative') + ' postingResult';
<?php endif; ?>
}

function selectAccounts(field, type, active)
{
    accounts = _get(field);
    for (i = 0; i < accounts.length; i++) {
        if (accounts.options[i].className.indexOf(type) != -1) {
            accounts.options[i].selected = active;
        }
    }
}

function autofillPostings() {
    datefields = _getall('date[]');
    assetfields = _getall('asset[]');
    accountfields = _getall('account[]');
    descfields = _getall('desc[]');
    amountfields = _getall('amount[]');

    for (j = 0; j < 12; j++) {
        if (!datefields[j]) {
            break;
        }

        if (j == 0) {
            year = convertDateToStamp(datefields[j].value).getFullYear().toString();
        } else {
            assetfields[j].selectedIndex = assetfields[j - 1].selectedIndex + (assetfields[j - 1].options[0].value == '' ? 0 : 1);
            accountfields[j].selectedIndex = accountfields[j - 1].selectedIndex + (accountfields[j - 1].options[0].value == '' ? 0 : 1);
            if (descfields[j].value == '') {
                descfields[j].value = descfields[j - 1].value;
            }
            if (amountfields[j].value == '') {
                amountfields[j].value = amountfields[j - 1].value;
            }
        }
        datefields[j].value = convertStampToDate(new Date(year, j, 1));
    }
    
    updateResult();
    updateAssetResult(assetfields[0]);
}

function Submit(action) {
    return true;
}

// -->
</script>
