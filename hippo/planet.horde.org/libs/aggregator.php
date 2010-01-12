<?php


include_once('MDB2.php');
include_once('utf2entities.php');

include_once('magpierss/rss_fetch.inc');

class aggregator {

    var $mdb = null;

    function __construct() {
        $this->mdb = MDB2::connect($GLOBALS['BX_config']['dsn']);
        if(MDB2::isError($this->mdb)) {
            die('unable to connect to db');
        }
    }

    function aggregateAllBlogs($id = null) {
    $where = '';
	if ($id) {
		$where = "where ID = $id";
	}
       $res = $this->mdb->query("select ID,blogsID as blogsid, link, cats, section from feeds $where");
       if (MDB2::isError($res)) {
           print $res->getMessage();
           print "\n";
           print $res->getUserinfo();
           die();
       }
       while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
           //get remote feed from magpie
           $feed = $this->getRemoteFeed($row['link']);
           if(!$feed) {
               continue;
           }
           //check if this blog already exists
           if (!$feed->channel['link']) {
               print "NO channel/link... PLEASE FIX THIS\n";
               continue;
           }

           $blog = $this->getBlogEntry($feed->channel['link']);
           $blog = $this->mdb->queryRow ("select * from blogs where id = ".(int)$row['blogsid'], null,MDB2_FETCHMODE_ASSOC);
           $newBlog = false;
           if (!$blog) {
               // $id = $this->insertBlogEntry($feed->channel);
               // print "new Blog: " .$feed->channel['title'] ."\n";
               // $newBlog = true;
           } else {
               //TODO: check for changed channel entries
               $id = $blog['id'];
               if ($feed->channel['title'] && $blog['title'] != $feed->channel['title'] && $row['section'] != 'comments') {
                    // $this->updateBlogEntry($feed->channel, $id);
               }
           }
           // update id, if not the same
           if ($row['blogsid'] != $id) {
               // $this->updateFeedBlogID($row['link'], $id);
            }

            //loop through feeds

            foreach ($feed->items as $item) {
                if (isset($item['guid'])) {
                    $guid = $item['guid'];
                } else if (isset($item['id'])) {
                    $guid = $item['id'];
		    $item['guid'] = $item['id'];
	        } else {
                    $guid = $item['link'];
		    $item['guid'] = $item['link'];
                }

               if (!isset($item['content']['encoded']) && isset($item['atom_content'])) {
			$item['content']['encoded'] = $item['atom_content'];
		}
                $item['md5']  = $this->generateMD5($item);

                $feedInDB = $this->getEntry($guid);
                if (!$feedInDB) {
                    // check for category stuff
                    // we only do that for new entries
                    if (isset($item['dc']['subject'])) {
                        $item['category'] = $item['dc']['subject'];
                    }

                    if ($row['cats']) {
                        $cats = explode(",",$row['cats']);
                        $hit = false;
                        foreach ($cats as $cat) {
                            if (strpos($item['category'],$cat) !== false) {
                                $hit = true;
                            }
                        }

                        if (!$hit) {
                            print $item['title'] . " - " . $item['category'] . " not in list\n";
                            continue;
                        }
                    }
                    // insert it in the db
                    $item = $this->truncateEntries($item);
                    $this->insertEntry($item, $row['id'], array("newBlog"=>$newBlog));
                } else if ($item['md5'] != $feedInDB['md5']) {
                    $item = $this->truncateEntries($item);
                    $this->updateEntry($item,$feedInDB['id']);
                }

            }
       }
    }

    function truncateEntries($item) {
        $maxsize = 5000;
        if (isset($item['content']['encoded']) && strlen($item['content']['encoded']) > $maxsize + 500) {
            print "TRUNCATE content_encoded on ". $item['title'] ."\n";
            $morebytes = (strlen($item['content']['encoded']) - $maxsize);
            $item['content']['encoded'] = $this->getBody(substr($item['content']['encoded'],0,$maxsize));
            $item['content']['encoded'] .= '<p><i>Truncated by Planet Horde, read more at <a href="'.$item['link'].'">the original</a> (another ' . $morebytes .' bytes)</i></p>';
        } else if (isset($item['description']) && strlen($item['description']) > $maxsize + 500) {
            print "TRUNCATE description ". $item['title'] ."\n";
            $morebytes = (strlen($item['description']) - $maxsize);

            $item['description'] = $this->getBody(substr($item['description'],0,$maxsize));
            $item['description'] .= '<p><i>Truncated by Planet Horde, read more at <a href="'.$item['link'].'">the original</a> (another ' . $morebytes .' bytes)</i></p>';
        }
        return $item;
    }

    function getBody($html) {

        $d = new DomDocument();
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$html.'</body>';
        @$d->loadHTML($html);
        $xp = new domxpath($d);
        $res = $xp->query("/html/body/node()");
        $body = "";
        foreach ($res as $node) {
            $body .= $d->saveXML($node);
        }
        return $body;
    }

    function generateMD5($item) {
        return hash('md5', $item['title'] .$item['link'] . (isset($item['description']) ? $item['description'] : '') . (isset($item['content']['encoded']) ? $item['content']['encoded'] : ''));
    }

    function updateEntry($item, $entryID) {
       $date =  $this->getDcDate($item, 0,true);
       $query = "update entries set " .
       " link =  '" .mysql_escape_string(utf2entities($item['link'])) . "'," .
       " title =  '" .mysql_escape_string(utf2entities($item['title'])) . "'," .
       " description=  '" .mysql_escape_string(utf2entities($item['description'])) . "'," .
       " content_encoded=  '" . mysql_escape_string(utf2entities($item['content']['encoded'])) . "',";
       if ($date) {
        $query .= " dc_date = '".$date."',";
       }

       $query .= " md5=  '" .$item['md5'] . "' ".
       " where ID = $entryID";
       print "update " . $item['title'] ."\n";
        $res = $this->mdb->query($query);
       if (MDB2::isError($res)) {
            print "DB ERROR: ". $res->getMessage() . "\n". $res->getUserInfo(). "\n";
            return false;
        } else {
            return true;
        }
    }
    function insertEntry($item,$feedID, $options = array()) {
        $id =  $this->mdb->nextID("planet");
        if (isset($options['newBlog']) && $options['newBlog']) {
            $offset = - 3600 * 144; // offset back to 6 days ago.needed for new blogs without pubdate/dcdate
        } else {
            $offset = 0;
        }
        if (!isset($item['guid']) || $item['guid'] == '') {
            $item['guid'] = $item['link'];
        }

        $date =  $this->getDcDate($item, $offset);

        $query = "insert into entries (ID,feedsID, title,link, guid,description,dc_date, dc_creator, content_encoded, md5) VALUES (".        $id . "," .
        $feedID . ",'" .

        mysql_escape_string(utf2entities($item['title'])) . "','" .
        mysql_escape_string(trim($item['link'])) . "','" .
        mysql_escape_string(($item['guid'])) . "','" .
        mysql_escape_string(utf2entities($item['description'])) . "','".
        $date . "','" .
        $item['dc']['creator'] . "','" .
        mysql_escape_string(utf2entities($item['content']['encoded'])) . "','".
        $item['md5'] . "')";

        print "insert " . $item['title'] ."\n";
        $res = $this->mdb->query($query);
        if (MDB2::isError($res)) {
            print "DB ERROR: ". $res->getMessage() . "\n". $res->getUserInfo(). "\n";
            return false;
        } else {
            return $id;
        }
    }

    function getDcDate($item, $nowOffset = 0, $returnNull = false) {
        //we want the dates in UTC... Looks like MySQL can't handle timezones...
        //putenv("TZ=UTC");
        if (isset($item['dc']['date'])) {
            $dcdate = $this->fixdate($item['dc']['date']);
        } elseif (isset($item['pubdate'])) {
            $dcdate = $this->fixdate($item['pubdate']);
        } elseif (isset($item['issued'])) {
            $dcdate = $this->fixdate($item['issued']);
        } elseif (isset($item['created'])) {
            $dcdate = $this->fixdate($item['created']);
        } elseif (isset($item['modified'])) {
            $dcdate = $this->fixdate($item['modified']);
        } elseif ($returnNull) {
            return NULL;
        } else {
            //TODO: Find a better alternative here
            $dcdate = gmdate("Y-m-d H:i:s O",time() + $nowOffset);
        }
        return $dcdate;

    }

    function fixdate($date) {
        $date =  preg_replace("/([0-9])T([0-9])/","$1 $2",$date);
        $date =  preg_replace("/([\+\-][0-9]{2}):([0-9]{2})/","$1$2",$date);
 	$time = strtotime($date);
        //if time is too much in the future (more than 1 hours)
        // set it to now()
        if (($time - time()) > 3600) {
                $time = time();
        }
        $date =  gmdate("Y-m-d H:i:s O",$time);
        return $date;
    }

    function updateFeedBlogID($url, $id) {

        $query = "update feeds set blogsID = $id where link = '$url'";
        $res = $this->mdb->query($query);
        if (MDB2::isError($res)) {
            print "DB ERROR: ". $res->getMessage() . "\n". $res->getUserInfo(). "\n";
            return false;
        } else {
            return $id;
        }
    }

    function insertBlogEntry($channel) {

        $id =  $this->mdb->nextID("planet");
        $query = "insert into blogs (ID,title,link,description) VALUES (".
        $id . ",'" .
        mysql_escape_string($channel['title']) . "','" .
        mysql_escape_string($channel['link']) . "','" .
        mysql_escape_string($channel['description']) . "')";
        $res = $this->mdb->query($query);
        if (MDB2::isError($res)) {
            print "DB ERROR: ". $res->getMessage() . "\n". $res->getUserInfo(). "\n";
            return false;
        } else {
            return $id;
        }
    }


     function updateBlogEntry($channel,$id) {
        $query = "update blogs set
        title =  '".mysql_escape_string($channel['title']) . "',
        link = '".mysql_escape_string($channel['link']) . "',
        description = '".mysql_escape_string($channel['description']) . "' where ID = ". $id;

        $res = $this->mdb->query($query);
        if (MDB2::isError($res)) {
            print "DB ERROR: ". $res->getMessage() . "\n". $res->getUserInfo(). "\n";
            return false;
        } else {
            return $id;
        }
    }

    function getBlogEntry($url) {
         return  $this->mdb->queryRow ("select * from blogs where link = '$url'",null,MDB2_FETCHMODE_ASSOC);
    }

    function getFeedEntry($url) {
         return  $this->mdb->queryRow ("select * from feeds where link = '$url'",null,MDB2_FETCHMODE_ASSOC);
    }
    function getEntry($url) {
         return  $this->mdb->queryRow ("select * from entries where guid = '$url'",null,MDB2_FETCHMODE_ASSOC);
    }

    function getRemoteFeed($url) {
        if ($feed = fetch_rss($url)) {
            return $feed;
        } else {
            print "$url is not a valid feed \n";
            return false;
        }
    }

}
