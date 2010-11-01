-- SQL script for adding the tag tables to Jonah.

CREATE TABLE jonah_tags (
    tag_id         INT NOT NULL,
    tag_name       VARCHAR(255) NOT NULL,
---
    PRIMARY KEY (tag_id)
);

CREATE TABLE jonah_stories_tags (
    story_id     INT NOT NULL,
    channel_id   INT NOT NULL,
    tag_id       INT NOT NULL,
---
    PRIMARY KEY (story_id, channel_id, tag_id)
);
