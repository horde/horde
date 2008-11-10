CREATE TABLE imp_sentmail (
    sentmail_id        BIGINT NOT NULL,
    sentmail_who       VARCHAR(255) NOT NULL,
    sentmail_ts        BIGINT NOT NULL,
    sentmail_messageid VARCHAR(255) NOT NULL,
    sentmail_action    VARCHAR(32) NOT NULL,
    sentmail_recipient VARCHAR(255) NOT NULL,
    sentmail_success   INT NOT NULL,
--
    PRIMARY KEY (sentmail_id)
);

CREATE INDEX sentmail_ts_idx ON imp_sentmail (sentmail_ts);
CREATE INDEX sentmail_who_idx ON imp_sentmail (sentmail_who);
CREATE INDEX sentmail_success_idx ON imp_sentmail (sentmail_success);
