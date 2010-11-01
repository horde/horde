-- $Horde: trean/scripts/sql/trean.sql,v 1.6 2008/11/25 20:47:27 chuck Exp $

CREATE TABLE trean_bookmarks (
    bookmark_id             INT NOT NULL,
    folder_id               INT NOT NULL,
    bookmark_url            VARCHAR(1024) NOT NULL,
    bookmark_title          VARCHAR(255),
    bookmark_description    VARCHAR(1024),
    bookmark_clicks         INT DEFAULT 0,
    bookmark_rating         INT,
    favicon_id              INT,
    bookmark_http_status    VARCHAR(5),
--
    PRIMARY KEY (bookmark_id)
);
CREATE INDEX trean_bookmarks_folder_idx ON trean_bookmarks (folder_id);
CREATE INDEX trean_bookmarks_clicks_idx ON trean_bookmarks (bookmark_clicks);
CREATE INDEX trean_bookmarks_rating_idx ON trean_bookmarks (bookmark_rating);

CREATE TABLE trean_favicons (
    favicon_id        INT NOT NULL,
    favicon_url       TEXT NOT NULL,
    favicon_updated   INT NOT NULL,
--
    PRIMARY KEY (favicon_id)
);
CREATE INDEX trean_favicons_url_idx ON trean_favicons (favicon_url(255));
