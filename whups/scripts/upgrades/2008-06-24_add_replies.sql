CREATE TABLE whups_replies (
    type_id             INT NOT NULL,
    reply_id            INT NOT NULL,
    reply_name          VARCHAR(255) NOT NULL,
    reply_text          TEXT NOT NULL,
--
    PRIMARY KEY (reply_id)
);
CREATE INDEX whups_reply_type_idx ON whups_replies (type_id);
CREATE INDEX whups_reply_name_idx ON whups_replies (reply_name);
