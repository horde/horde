CREATE TABLE vilma_domains (
  domain_id         INT DEFAULT 0 NOT NULL,
  domain_name       VARCHAR(128) DEFAULT '' NOT NULL,
  domain_transport  VARCHAR(128) DEFAULT '' NOT NULL,
  domain_max_users  INT DEFAULT 0 NOT NULL,
  domain_quota      INT DEFAULT 0 NOT NULL,
  domain_key        VARCHAR(64),
--
  PRIMARY KEY  (domain_id),
  UNIQUE (domain_name)
);

CREATE TABLE vilma_users (
  user_id           INT DEFAULT 0 NOT NULL,
  user_name         VARCHAR(255) DEFAULT '' NOT NULL,
  user_clear        VARCHAR(255) DEFAULT '' NOT NULL,
  user_crypt        VARCHAR(255) DEFAULT '' NOT NULL,
  user_full_name    VARCHAR(255) DEFAULT '' NOT NULL,
  user_uid          INT NOT NULL,
  user_gid          INT NOT NULL,
  user_home_dir     VARCHAR(255) DEFAULT '' NOT NULL,
  user_mail_dir     VARCHAR(255) DEFAULT '' NOT NULL,
  user_mail_quota   INT DEFAULT 0 NOT NULL,
  user_ftp_dir      VARCHAR(255) DEFAULT NULL,
  user_ftp_quota    INT DEFAULT NULL,
  user_enabled      SMALLINT DEFAULT 1 NOT NULL,
--
  PRIMARY KEY (user_id),
  UNIQUE (user_name)
);

CREATE TABLE vilma_virtuals (
  virtual_id            INT DEFAULT 0 NOT NULL,
  virtual_email         VARCHAR(128) DEFAULT '' NOT NULL,
  virtual_destination   VARCHAR(128) DEFAULT '' NOT NULL,
--
  PRIMARY KEY (virtual_id)
);
