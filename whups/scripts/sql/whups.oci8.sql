--
-- Copyright 2001-2005 Robert E. Coyle <robertecoyle@hotmail.com>
--
-- See the enclosed file LICENSE for license information (BSD). If you
-- did not receive this file, see http://www.horde.org/licenses/bsdl.php.
--
-- Database definitions for Whups

CREATE TABLE whups_tickets (
    ticket_id           NUMBER(16) NOT NULL,          -- unique ticket id
    ticket_summary      VARCHAR2(255),                -- summary of the ticket
    user_id_requester   VARCHAR2(255) NOT NULL,       -- user id of the creator of this ticket
    queue_id            NUMBER(16) NOT NULL,          -- queue id that this ticket refers to
    version_id          NUMBER(16),                   -- version id that this ticket refers to
    type_id             NUMBER(16) NOT NULL,          -- id into the type table, describing the type of ticket
    state_id            NUMBER(16) NOT NULL,          -- state of this ticket, meaning depends on the type of the ticket
    priority_id         NUMBER(16) NOT NULL,          -- priority, meaning depends on the type of the ticket
    ticket_timestamp    NUMBER(16) NOT NULL,          -- redundant but useful, mirrored in the comment and log
    ticket_due          NUMBER(16),                   -- optional ticket due date
    date_updated        NUMBER(16),                   -- date of last update
    date_assigned       NUMBER(16),                   -- date of assignment
    date_resolved       NUMBER(16),                   -- date of resolving
--
    PRIMARY KEY (ticket_id)
);
CREATE INDEX whups_ticket_queue_idx ON whups_tickets (queue_id);
CREATE INDEX whups_ticket_state_idx ON whups_tickets (state_id);
CREATE INDEX whups_ticket_requester_idx ON whups_tickets (user_id_requester);
CREATE INDEX whups_ticket_version_idx ON whups_tickets (version_id);
CREATE INDEX whups_ticket_priority_idx ON whups_tickets (priority_id);

CREATE TABLE whups_ticket_owners (
    ticket_id           NUMBER(16) NOT NULL,
    ticket_owner        VARCHAR2(255) NOT NULL
--
    PRIMARY KEY (ticket_id, ticket_owner)
);
CREATE INDEX whups_ticket_owner_ticket_idx ON whups_ticket_owners (ticket_id);
CREATE INDEX whups_ticket_owner_owner_idx ON whups_ticket_owners (ticket_owner);

CREATE TABLE whups_guests (
    guest_id            VARCHAR2(255) NOT NULL,
    guest_email         VARCHAR2(255) NOT NULL,
--
    PRIMARY KEY (guest_id)
);

CREATE TABLE whups_queues (
    queue_id            NUMBER(16) NOT NULL,
    queue_name          VARCHAR2(64) NOT NULL,
    queue_description   VARCHAR2(255),
    queue_versioned     NUMBER(1) DEFAULT 0 NOT NULL,
    queue_slug          VARCHAR2(64),
    queue_email         VARCHAR2(64),
--
    PRIMARY KEY (queue_id)
);

CREATE TABLE whups_queues_users (
    queue_id            NUMBER(16) NOT NULL,
    user_uid            VARCHAR2(255) NOT NULL,
--
    PRIMARY KEY (queue_id, user_uid)
);

CREATE TABLE whups_types (
    type_id             NUMBER(16) NOT NULL,
    type_name           VARCHAR2(64) NOT NULL,
    type_description    VARCHAR2(255),
--
    PRIMARY KEY (type_id)
);

CREATE TABLE whups_types_queues (
    type_id             NUMBER(16) NOT NULL,
    queue_id            NUMBER(16) NOT NULL,
    type_default        NUMBER(1) DEFAULT 0 NOT NULL
);
CREATE INDEX whups_type_queue_idx ON whups_types_queues (queue_id, type_id);

CREATE TABLE whups_states (
    state_id            NUMBER(16) NOT NULL,
    type_id             NUMBER(16) NOT NULL,
    state_name          VARCHAR2(64) NOT NULL,
    state_description   VARCHAR2(255),
    state_category      VARCHAR2(16),
    state_default       NUMBER(1) DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (state_id)
);
CREATE INDEX whups_state_type_idx ON whups_states (type_id);
CREATE INDEX whups_state_category_idx ON whups_states (state_category);

CREATE TABLE whups_replies (
    type_id             NUMBER(16) NOT NULL,
    reply_id            NUMBER(16) NOT NULL,
    reply_name          VARCHAR2(255) NOT NULL,
    reply_text          CLOB NOT NULL,
--
    PRIMARY KEY (reply_id)
);
CREATE INDEX whups_reply_type_idx ON whups_replies (type_id);
CREATE INDEX whups_reply_name_idx ON whups_replies (reply_name);

CREATE TABLE whups_attributes_desc (
    attribute_id          NUMBER(16) NOT NULL,
    type_id               NUMBER(16) NOT NULL,
    attribute_name        VARCHAR2(64) NOT NULL,
    attribute_description VARCHAR2(255),
    attribute_type        VARCHAR2(255) DEFAULT 'text' NOT NULL,
    attribute_params      CLOB,
    attribute_required    NUMBER(8),
--
    PRIMARY KEY (attribute_id)
);

CREATE TABLE whups_attributes (
    ticket_id           NUMBER(16) NOT NULL,
    attribute_id        NUMBER(16) NOT NULL,
    attribute_value     VARCHAR2(255)
);

CREATE TABLE whups_comments (
    comment_id          NUMBER(16) NOT NULL,
    ticket_id           NUMBER(16) NOT NULL,
    user_id_creator     VARCHAR2(255) NOT NULL,
    comment_text        CLOB,
    comment_timestamp   NUMBER(16),
--
    PRIMARY KEY (comment_id)
);
CREATE INDEX whups_comment_ticket_idx ON whups_comments (ticket_id);

CREATE TABLE whups_logs (
    log_id              NUMBER(16) NOT NULL,
    transaction_id      NUMBER(16) NOT NULL,
    ticket_id           NUMBER(16) NOT NULL,
    log_timestamp       NUMBER(16) NOT NULL,
    log_type            VARCHAR2(255) NOT NULL,
    log_value           VARCHAR2(255),
    log_value_num       NUMBER(16),
    user_id             VARCHAR2(255) NOT NULL,
--
    PRIMARY KEY (log_id)
);

CREATE INDEX whups_log_transaction_idx ON whups_logs (transaction_id);
CREATE INDEX whups_log_ticket_id_idx ON whups_logs (ticket_id);
CREATE INDEX whups_log_timestamp_idx ON whups_logs (log_timestamp);

CREATE TABLE whups_priorities (
    priority_id           NUMBER(16) NOT NULL,
    type_id               NUMBER(16) NOT NULL,
    priority_name         VARCHAR2(64),
    priority_description  VARCHAR2(255),
    priority_default      NUMBER(1) DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (priority_id)
);

CREATE TABLE whups_versions (
    version_id          NUMBER(16) NOT NULL,
    queue_id            NUMBER(16) NOT NULL,
    version_name        VARCHAR2(64),
    version_description VARCHAR2(255),
    version_active      NUMBER(1) DEFAULT 1,
--
    PRIMARY KEY (version_id)
);
CREATE INDEX whups_versions_active_idx ON whups_versions (version_active);

CREATE TABLE whups_ticket_listeners (
    ticket_id           NUMBER(16) NOT NULL,
    user_uid            VARCHAR2(255) NOT NULL
);
CREATE INDEX whups_ticket_listeners_ticket_idx ON whups_ticket_listeners (ticket_id);

CREATE TABLE whups_queries (
    query_id            NUMBER(16) NOT NULL,
    query_parameters    CLOB,
    query_object        CLOB,
--
    PRIMARY KEY (query_id)
);

CREATE TABLE whups_shares (
    share_id NUMBER(16) NOT NULL,
    share_name VARCHAR2(255) NOT NULL,
    share_owner VARCHAR2(255) NOT NULL,
    share_flags NUMBER(8) DEFAULT 0 NOT NULL,
    perm_creator NUMBER(8) DEFAULT 0 NOT NULL,
    perm_default NUMBER(8) DEFAULT 0 NOT NULL,
    perm_guest NUMBER(8) DEFAULT 0 NOT NULL,
    attribute_name VARCHAR2(255) NOT NULL,
    attribute_slug VARCHAR2(255),
--
    PRIMARY KEY (share_id)
);

CREATE INDEX whups_shares_share_name_idx ON whups_shares (share_name);
CREATE INDEX whups_shares_share_owner_idx ON whups_shares (share_owner);
CREATE INDEX whups_shares_perm_creator_idx ON whups_shares (perm_creator);
CREATE INDEX whups_shares_perm_default_idx ON whups_shares (perm_default);
CREATE INDEX whups_shares_perm_guest_idx ON whups_shares (perm_guest);

CREATE TABLE whups_shares_groups (
    share_id NUMBER(16) NOT NULL,
    group_uid VARCHAR2(255) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX whups_shares_groups_share_id_idx ON whups_shares_groups (share_id);
CREATE INDEX whups_shares_groups_group_uid_idx ON whups_shares_groups (group_uid);
CREATE INDEX whups_shares_groups_perm_idx ON whups_shares_groups (perm);

CREATE TABLE whups_shares_users (
    share_id NUMBER(16) NOT NULL,
    user_uid VARCHAR2(255) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX whups_shares_users_share_id_idx ON whups_shares_users (share_id);
CREATE INDEX whups_shares_users_user_uid_idx ON whups_shares_users (user_uid);
CREATE INDEX whups_shares_users_perm_idx ON whups_shares_users (perm);
