CREATE TABLE jonah_channels (
    channel_id        INT NOT NULL,
    channel_slug      VARCHAR(64) NOT NULL,
    channel_name      VARCHAR(255) NOT NULL,
    channel_type      SMALLINT NOT NULL,
    channel_full_feed SMALLINT DEFAULT 0 NOT NULL,
    channel_desc      VARCHAR(255),
    channel_interval  INT,
    channel_url       VARCHAR(255),
    channel_link      VARCHAR(255),
    channel_page_link VARCHAR(255),
    channel_story_url VARCHAR(255),
    channel_img       VARCHAR(255),
    channel_updated   INT,
--
    PRIMARY KEY (channel_id)
);

CREATE TABLE jonah_stories (
    story_id        INT NOT NULL,
    channel_id      INT NOT NULL,
    story_author    VARCHAR(255) NOT NULL,
    story_title     VARCHAR(255) NOT NULL,
    story_desc      TEXT,
    story_body_type VARCHAR(255) NOT NULL,
    story_body      TEXT,
    story_url       VARCHAR(255),
    story_permalink VARCHAR(255),
    story_published INT,
    story_updated   INT NOT NULL,
    story_read      INT NOT NULL,
--
    PRIMARY KEY (story_id)
);

CREATE TABLE jonah_tags (
    tag_id         INT NOT NULL,
    tag_name       VARCHAR(255) NOT NULL,
--
    PRIMARY KEY (tag_id)
);

CREATE TABLE jonah_stories_tags (
    story_id     INT NOT NULL,
    channel_id   INT NOT NULL,
    tag_id       INT NOT NULL,
--
    PRIMARY KEY (story_id, channel_id, tag_id)
);

CREATE INDEX jonah_stories_channel_idx ON jonah_stories (channel_id);
CREATE INDEX jonah_stories_published_idx ON jonah_stories (story_published);
CREATE INDEX jonah_stories_url_idx ON jonah_stories (story_url);
CREATE INDEX jonah_channels_type_idx ON jonah_channels (channel_type);
