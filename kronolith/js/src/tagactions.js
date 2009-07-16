function addTag(resource, type, endpoint)
{
    if (!$('newtags-input_' + resource).value.blank()) {
        var params = new Object();
        params.imple="/action=add/resource=" + resource + "/type=" + type + "/tags=" + $('newtags-input_' + resource).value;
        new Ajax.Updater({success:'tags_' + resource},
                         endpoint,
                         {
                             method: 'post',
                             parameters: params,
                             onComplete: function() {$('newtags-input_' + resource).value = "";}
                         }
        );
    }

    return true;
}

function removeTag(resource, type, tagid, endpoint)
{
    var params = new Object();
    params.imple = "/action=remove/resource=" + resource + "/type=" + type + "/tags=" + tagid;
    new Ajax.Updater({success:'tags_' + resource},
                     endpoint,
                     {
                         method: 'post',
                         parameters: params
                     }
    );
    
    return true;
}
