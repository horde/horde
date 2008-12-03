
function getFeed() {
    RedBox.loading();
    new Ajax.Updater('feed_content',
                    document.feed_select.action,
                    {
                        onComplete: function(){ RedBox.close();},
                        parameters: { feed_id: $F('feed_id') },
                        onFailure: function(){ RedBox.close(); alert('Error'); },
                        asynchronous: true
                    });
}

function send_news_mail() {
    RedBox.loading();
    new Ajax.Updater('mail_send',
                    document.mail_send.action,
                    {
                        onComplete: function(){ RedBox.close();},
                        onFailure: function(){ RedBox.close(); alert('Error'); },
                        asynchronous: true,
                        parameters: { fee: $F('email') }
                    });
}