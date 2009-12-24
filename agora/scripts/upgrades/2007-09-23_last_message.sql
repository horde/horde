ALTER TABLE agora_messages ADD last_message_id INT DEFAULT 0 NOT NULL;
ALTER TABLE agora_messages ADD last_message_author VARCHAR(255);

ALTER TABLE agora_forums ADD last_message_id INT DEFAULT 0 NOT NULL;
ALTER TABLE agora_forums ADD last_message_author VARCHAR(255) DEFAULT 0 NOT NULL;
ALTER TABLE agora_forums ADD last_message_timestamp INT DEFAULT 0 NOT NULL;
