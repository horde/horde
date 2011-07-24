CREATE TABLE trean_bookmarks (
    bookmark_id             INT UNSIGNED NOT NULL,
    user_id                 INT UNSIGNED NOT NULL,
    bookmark_url            VARCHAR(1024) NOT NULL,
    bookmark_title          VARCHAR(255),
    bookmark_description    VARCHAR(1024),
    bookmark_clicks         INT DEFAULT 0,
    bookmark_rating         INT,
    favicon_id              INT,
    bookmark_http_status    VARCHAR(5),
    PRIMARY KEY (bookmark_id)
);
CREATE INDEX trean_bookmarks_user_idx ON trean_bookmarks (user_id);
CREATE INDEX trean_bookmarks_clicks_idx ON trean_bookmarks (bookmark_clicks);
CREATE INDEX trean_bookmarks_rating_idx ON trean_bookmarks (bookmark_rating);

CREATE TABLE trean_favicons (
    favicon_id        INT NOT NULL,
    favicon_url       TEXT NOT NULL,
    favicon_updated   INT NOT NULL,
    PRIMARY KEY (favicon_id)
);
CREATE INDEX trean_favicons_url_idx ON trean_favicons (favicon_url(255));
