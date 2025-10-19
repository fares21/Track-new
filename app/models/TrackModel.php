<?php
namespace Appmodels;

use AppcoreDatabase;
use PDO;

class TrackModel {
    private $pdo;

    public function __construct(array $cfg) {
        $this->pdo = Database::conn($cfg);
    }

    public function findByNumber(string $num): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM track_items WHERE track_number = ? LIMIT 1");
        $stmt->execute([$num]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createItem(string $num, string $carrier, array $meta = []): int {
        $stmt = $this->pdo->prepare("INSERT INTO track_items (track_number, carrier_code, last_status, last_update_at, metadata) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$num, $carrier, $meta['last_status'] ?? '', $meta['last_update_at'] ?? date('Y-m-d H:i:s'), json_encode($meta, JSON_UNESCAPED_UNICODE)]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateItem(int $id, string $lastStatus, string $lastUpdateAt, array $meta) {
        $stmt = $this->pdo->prepare("UPDATE track_items SET last_status = ?, last_update_at = ?, metadata = ? WHERE id = ?");
        $stmt->execute([$lastStatus, $lastUpdateAt, json_encode($meta, JSON_UNESCAPED_UNICODE), $id]);
    }

    public function replaceEvents(int $itemId, array $events) {
        $this->pdo->beginTransaction();
        $del = $this->pdo->prepare("DELETE FROM track_events WHERE track_item_id = ?");
        $del->execute([$itemId]);
        if (!empty($events)) {
            $ins = $this->pdo->prepare("INSERT INTO track_events (track_item_id, event_time, location, status_text) VALUES (?, ?, ?, ?)");
            foreach ($events as $ev) {
                $ins->execute([$itemId, $ev['time'], $ev['location'], $ev['status']]);
            }
        }
        $this->pdo->commit();
    }

    public function getEvents(int $itemId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM track_events WHERE track_item_id = ? ORDER BY event_time ASC");
        $stmt->execute([$itemId]);
        return $stmt->fetchAll();
    }

    public function addToUserList(string $userId, int $itemId) {
        $stmt = $this->pdo->prepare("SELECT id FROM user_lists WHERE user_identifier = ? AND track_item_id = ? LIMIT 1");
        $stmt->execute([$userId, $itemId]);
        if (!$stmt->fetch()) {
            $ins = $this->pdo->prepare("INSERT INTO user_lists (user_identifier, track_item_id) VALUES (?, ?)");
            $ins->execute([$userId, $itemId]);
        }
    }

    public function getUserList(string $userId): array {
        $stmt = $this->pdo->prepare("SELECT ul.id as link_id, ti.* FROM user_lists ul JOIN track_items ti ON ti.id = ul.track_item_id WHERE ul.user_identifier = ? ORDER BY ul.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function removeFromUserList(string $userId, int $linkId): bool {
        $stmt = $this->pdo->prepare("DELETE ul FROM user_lists ul WHERE ul.id = ? AND ul.user_identifier = ?");
        return $stmt->execute([$linkId, $userId]);
    }

    public function dueForRefresh(int $minutes): array {
        $stmt = $this->pdo->prepare("SELECT * FROM track_items WHERE last_update_at IS NULL OR last_update_at < DATE_SUB(NOW(), INTERVAL ? MINUTE) ORDER BY last_update_at ASC LIMIT 100");
        $stmt->execute([$minutes]);
        return $stmt->fetchAll();
    }
}
