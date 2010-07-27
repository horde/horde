<?php
/**
 * Horde_Service_Twitter_Statuses class for updating, retrieving user statuses.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Statuses
{
    /**
     * Endpoint for status api requests
     *
     * @var string
     */
    private $_endpoint = 'http://api.twitter.com/1/statuses/';

    /**
     * Format to use json or xml
     *
     * @var string
     */
    private $_format = 'json';

    /**
     * Const'r
     *
     * @param Horde_Service_Twiiter $twitter
     */
    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Obtain the requested status
     *
     * @return mixed  The method call results.
     */
    public function show($id)
    {
        $url = $this->_endpoint . 'show.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

    /**
     * Destroy the specified status update, obviously only if the current user
     * is the author of the update.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0destroy
     *
     * @param string $id  The status id
     *
     * @return string
     */
    public function destroy($id)
    {
        $url = $this->_endpoint . 'destroy.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

    /**
     * Update the current user's status.
     *
     * @param string $status  The new status text.
     * @param array $params   Any additional parameters.
     *   <pre>
     *     in_reply_to_status_id  - the status id this tweet is in response to.
     *   </pre>
     *
     * @return string
     */
    public function update($status, $params = array())
    {
        $url = $this->_endpoint . 'update.' . $this->_format;
        $params['status'] = $status;

        return $this->_twitter->request->post($url, $params);
    }

    /**
     * Obtain the friendsTimeline.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-friends_timeline
     *
     * NOTE: According to the API docs, this method is deprecated and will be
     * going away in a future version of the API. This is to be replaced by
     * home_timeline.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-home_timeline
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id         - Only tweets more recent the indicated tweet id
     *     max_id           - Only tweets older then the indeicated tweet id
     *     count            - Only return this many tweets (twitter limit = 200)
     *     page             - The page number to return (note there are
     *                        pagination limits)
     *     include_rts      - Include retweets
     *     include_entities - Include twitter entities (will be mandatory in
     *                        future twitter api release).
     *   </pre>
     *
     * @return string
     */
    public function friendsTimeline($params = array())
    {
        $url = $this->_endpoint . 'friends_timeline.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns the 20 most recent statuses, including retweets, posted by the
     * authenticating user and that user's friends. This is the equivalent of
     * /timeline/home on the Web.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-home_timeline
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     *
     * @return string
     */
    public function homeTimeline($params = array())
    {
        $url = $this->_endpoint . 'home_timeline.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns the 20 most recent retweets posted by the authenticating user.
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     *
     * @return string
     */
    public function retweetedByMe($params = array())
    {
        $url = $this->_endpoint . 'retweeted_by_me.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns the 20 most recent retweets posted by the authenticating user's
     * friends.
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     *
     * @return string
     */
    public function retweetedToMe($params = array())
    {
        $url = $this->_endpoint . 'retweetedToMe.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns the 20 most recent tweets of the authenticated user that have
     * been retweeted by others.
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     *
     * @return string
     */
    public function retweetsOfMe($params = array())
    {
        $url = $this->_endpoint . 'retweets_of_me.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Retweets a tweet. Requires the id parameter of the tweet you are
     * retweeting. Request must be a POST or PUT.
     * Returns the original tweet with retweet details embedded.
     *
     * @params string id  The id for the tweet that is being retweeted.
     */
    public function retweet($id)
    {
        $url = $this->_endpoint . 'retweet/' . $id .  '.' . $this->_format;

        return $this->_twitter->request->post($url, array());
    }

    /**
     * Obtain the last 20 tweets from the public timeline. This is cached every
     * 60 seconds on Twitter's servers so we should eventually ensure this is
     * only actually requested every 60 seconds or greater.
     *
     * @return string
     */
    public function publicTimeline()
    {
        $url = $this->_endpoint . 'public_timeline.' . $this->_format;
        return $this->_twitter->request->get($url);
    }

    /**
     * Obtain the friendsTimeline.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-user_timeline
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     id         - For this user id or screen name.
     *                  Current user if left out.
     *     user_id    - Specfies the ID of the user for whom to return the
     *                  user_timeline. Helpful for disambiguating when a valid
     *                  user ID is also a valid screen name.
     *     screen_id  - Specfies the screen name of the user for whom to return
     *                  the user_timeline. Helpful for disambiguating when a
     *                  valid screen name is also a user ID.
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     * @return unknown_type
     */
    public function userTimeline($params = array())
    {
        $url = $this->_endpoint . 'user_timeline.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Obtain most recent 'mentions' for the current user. (i.e. all messages
     * that contain @username in the text).
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-mentions
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     since_id   - Only tweets more recent the indicated tweet id
     *     max_id     - Only tweets older then the indeicated tweet id
     *     count      - Only return this many tweets (twitter limit = 200)
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     * @return unknown_type
     */
    public function mentions($params = array())
    {
        $url = $this->_endpoint . 'mentions.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns a user's friends, each with current status inline. They are
     * ordered by the order in which they were added as friends, 100 at a time.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0friends
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     id         - For this user id or screen name.
     *                  Current user if left out.
     *     user_id    - Specfies the ID of the user for whom to return the
     *                  user_timeline. Helpful for disambiguating when a valid
     *                  user ID is also a valid screen name.
     *     screen_id  - Specfies the screen name of the user for whom to return
     *                  the user_timeline. Helpful for disambiguating when a
     *                  valid screen name is also a user ID.
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     * @return unknown_type
     */
    public function friends($params = array())
    {
        $url = $this->_endpoint . 'friends.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

    /**
     * Returns a user's followers, each with current status inline. They are
     * ordered by the order in which they were added as friends, 100 at a time.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0friends
     *
     * @param array $params  Parameters for the friends_timeline call
     *   <pre>
     *     id         - For this user id or screen name.
     *                  Current user if left out.
     *     user_id    - Specfies the ID of the user for whom to return the
     *                  user_timeline. Helpful for disambiguating when a valid
     *                  user ID is also a valid screen name.
     *     screen_id  - Specfies the screen name of the user for whom to return
     *                  the user_timeline. Helpful for disambiguating when a
     *                  valid screen name is also a user ID.
     *     page       - The page number to return (note there are pagination limits)
     *   </pre>
     * @return unknown_type
     */
    public function followers($params = array())
    {
        $url = $this->_endpoint . 'followers.' . $this->_format;
        return $this->_twitter->request->get($url, $params);
    }

}
