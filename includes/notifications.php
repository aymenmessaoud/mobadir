<?php
/**
 * Notifications Management Module
 */

require_once __DIR__ . '/../config/database.php';

function send_notification($user_id, $title, $message, $link = null) {
    $db = get_db_connection();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $link]);
}

function notify_club_followers($club_id, $title, $message, $link = null) {
    $db = get_db_connection();
    // Get all followers of this club
    $stmt = $db->prepare("SELECT volunteer_id FROM follows WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($followers)) {
        return;
    }

    $insert_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    foreach ($followers as $vol_id) {
        $insert_stmt->execute([$vol_id, $title, $message, $link]);
    }
}

function get_unread_notifications_count($user_id) {
    if (!$user_id) return 0;
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function get_user_notifications($user_id, $limit = 30) {
    if (!$user_id) return [];
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mark_notifications_as_read($user_id) {
    if (!$user_id) return;
    $db = get_db_connection();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
}
