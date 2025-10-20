-- إنشاء الجداول
CREATE TABLE IF NOT EXISTS track_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  track_number VARCHAR(100) NOT NULL,
  carrier_code VARCHAR(50),
  last_status VARCHAR(255),
  last_update_at DATETIME,
  metadata JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_track_number (track_number),
  KEY idx_carrier (carrier_code),
  KEY idx_last_update (last_update_at),
  UNIQUE KEY unique_track (track_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS track_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  track_item_id INT NOT NULL,
  event_time DATETIME,
  location VARCHAR(255),
  status_text VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (track_item_id) REFERENCES track_items(id) ON DELETE CASCADE,
  KEY idx_item_time (track_item_id, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_identifier VARCHAR(255) NOT NULL,
  track_item_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (track_item_id) REFERENCES track_items(id) ON DELETE CASCADE,
  KEY idx_user (user_identifier),
  UNIQUE KEY unique_user_track (user_identifier, track_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بيانات تجريبية
INSERT IGNORE INTO track_items (track_number, carrier_code, last_status, last_update_at, metadata)
VALUES
('1Z12345E1512345676', 'ups', 'In Transit', NOW(), JSON_OBJECT(
  'expected_delivery', DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), '%Y-%m-%d'),
  'provider_url', 'https://www.ups.com/track?tracknum=1Z12345E1512345676',
  'proof', JSON_OBJECT('hash','a1b2c3d4e5f6', 'ipfs','', 'generated_at', NOW())
));

SET @item_id = LAST_INSERT_ID();

INSERT IGNORE INTO track_events (track_item_id, event_time, location, status_text) VALUES
(@item_id, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Origin Facility', 'Created'),
(@item_id, DATE_SUB(NOW(), INTERVAL 4 DAY), 'Warehouse A', 'Picked Up'),
(@item_id, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Transit Hub', 'In Transit'),
(@item_id, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Local Center', 'Out for Delivery');
