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
  CONSTRAINT fk_comments_message FOREIGN KEY (message_id)
    REFERENCES messages(id) ON DELETE CASCADE
);

-- Ensure a covering index exists for message lookups while remaining idempotent.
SET @have_index := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'comments'
    AND INDEX_NAME = 'idx_comments_message_created_at'
);
SET @ddl := IF(@have_index = 0,
  'ALTER TABLE comments ADD INDEX idx_comments_message_created_at (message_id, created_at)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
