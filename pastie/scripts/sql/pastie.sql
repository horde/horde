--
-- Copyright 2001-2005 Robert E. Coyle <robertecoyle@hotmail.com>
--
-- See the enclosed file LICENSE for license information (BSD). If you
-- did not receive this file, see http://www.horde.org/licenses/bsdl.php.
--
-- Database definitions for Whups

CREATE TABLE pastie_pastes (
    paste_id		INT NOT NULL,		-- sequential id
    paste_uuid		VARCHAR(40) NOT NULL,	-- UUID
    paste_bin		VARCHAR(64) NOT NULL,	-- associated bin (FIXME)
    paste_title         VARCHAR(255),           -- optional title
    paste_syntax	VARCHAR(16) NOT NULL,	-- syntax for highlighting
    paste_content	TEXT,			-- paste content
    paste_owner		VARCHAR(255),		-- paster name
    paste_timestamp	INT NOT NULL,		-- date/time, Unix epoch
    PRIMARY KEY (paste_id),
    UNIQUE paste_id (paste_id),
    UNIQUE paste_uuid (paste_uuid)
);
    
