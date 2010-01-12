/**
 * Update news source feed
 *
 * $Id:$
 */
function getFeed() {

    RedBox.loading();

    new Ajax.Updater('feed_content',
                    document.feed_select.action,
                    {
                        onComplete: function() {
                                RedBox.close();
                            },
                        parameters: {
                                feed_id: $F('feed_id')
                            },
                        onFailure: function() {
                                RedBox.close();
                                alert('Error');
                            },
                        asynchronous: true
                    });
}
