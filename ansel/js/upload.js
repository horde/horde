AnselUpload = {
    doUploadNotification: function(s, g)
    {
        var params = {
            'params': 'g=' + g + '/s=' + s
        };
        new Ajax.Request(Ansel.ajax.uploadNotificationUrl + '/post=params', {
            method: 'post',
            parameters: params,
            onSuccess: function(r) {
                console.log(r);
            },
            onError: function(r) {
                console.log(r);
            }
        });
    }
}