function addTag()
{
    if (!$('addtag').value.blank()) {
        var params = new Object();
        params.requestType="TagActions/action=add/gallery=" + tagActions.gallery + "/tags=" + $('addtag').value;
        new Ajax.Updater({success:'tags'},
                         tagActions.url,
                         {
                             method: 'post',
                             parameters: params,
                             onComplete: function() {$('addtag').value = "";}
                         }
        );
    }

    return true;
}

function removeTag(resource, type, tagid, endpoint)
{
    var params = new Object();
    params.imple = "TagActions/action=remove/resource=" + resource + "/type=" + type + "/tags=" + tagid;
    new Ajax.Updater({success:'tags-' + resource},
                     endpoint,
                     {
                         method: 'post',
                         parameters: params
                     }
    );
    return true;
}

// Since onsubmit is never called when submitting programatically we
// can use this function to add tags when we press enter on the tag form.
function submitcheck()
{
    return !addTag();
}