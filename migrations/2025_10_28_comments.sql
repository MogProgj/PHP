-- Cave of Conspiracies migration: comments table
-- Apply with:
--   mysql -u <user> -p <database> < migrations/2025_10_28_comments.sql
-- Replace <user> and <database> with your credentials before running.

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  nickname VARCHAR(60) NOT NULL,
  body VARCHAR(240) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

ALTER TABLE comments
  ADD INDEX IF NOT EXISTS idx_comments_message_created_at (message_id, created_at);
