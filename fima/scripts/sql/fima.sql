CREATE TABLE fima_accounts (
    account_id      VARCHAR(32) NOT NULL,
    account_owner   VARCHAR(255) NOT NULL,
    account_number  VARCHAR(4) NOT NULL,
    account_type    VARCHAR(255) NOT NULL,
    account_name    VARCHAR(255) NOT NULL,
    account_desc    TEXT NOT NULL,
    account_eo      INT NOT NULL DEFAULT 0,
    account_closed  INT NOT NULL DEFAULT 0,
--
    PRIMARY KEY (account_id)
);

CREATE INDEX fima_account_owner_idx ON fima_accounts (account_owner);
CREATE INDEX fima_account_type_idx ON fima_accounts (account_type);

GRANT SELECT, INSERT, UPDATE, DELETE ON fima_accounts TO horde;

CREATE TABLE fima_postings (
    posting_id       VARCHAR(32) NOT NULL,
    posting_owner    VARCHAR(255) NOT NULL,
    posting_type     VARCHAR(255) NOT NULL,
    posting_date     INT NOT NULL,
    posting_asset    VARCHAR(32) NOT NULL,
    posting_account  VARCHAR(32) NOT NULL,
    posting_eo       INT NOT NULL DEFAULT 0,
    posting_amount   DECIMAL(10,2) NOT NULL,
    posting_desc     VARCHAR(255) NOT NULL,
--
    PRIMARY KEY (posting_id)
);

CREATE INDEX fima_posting_owner_idx ON fima_postings (posting_owner);
CREATE INDEX fima_posting_type_idx ON fima_postings (posting_type);
CREATE INDEX fima_posting_account_idx ON fima_postings (posting_account);
CREATE INDEX fima_posting_asset_idx ON fima_postings (posting_asset);


GRANT SELECT, INSERT, UPDATE, DELETE ON fima_postings TO horde;

