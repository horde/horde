function saveSearch(url) {
    RedBox.loading();
    new Ajax.Request(url, {
                parameters: {
                    ajax: 1
                },
                method: 'get',
                onSuccess: function(transport) {
                    RedBox.showHtml('<div id="RB_info">' + transport.responseText + '</div>');
                },
                onFailure: function(transport) {
                    RedBox.close();
                }
            });
}

        function updateStatus(statusText, inputNode) {
            {$spinner}.toggle();
            params = new Object();
            params.actionID = 'updateStatus';
            params.statusText = statusText;
            new Ajax.Updater({success:'currentStatus'},
                 '$endpoint',
                 {
                     method: 'post',
                     parameters: params,
                     onComplete: function() {inputNode.value = '';{$spinner}.toggle()},
                     onFailure: function() {{$spinner}.toggle()}
                 }
           );
        }