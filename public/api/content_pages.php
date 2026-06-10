<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_auth.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $admin = current_admin_user() || request_admin_token_is_valid();
    $where = $admin ? '' : " WHERE status = 'published'";

    try {
        $stmt = $conn->prepare("SELECT page_id, page_key, title, body, status, published_at, updated_at
                                FROM content_pages" . $where . "
                                ORDER BY page_key ASC");
        $stmt->execute();
        json_success('Content pages loaded.', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        json_error('Unable to load content pages.', 500);
    }
}

require_admin();

if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    json_error('Method not allowed.', 405);
}

$input = read_request_input();
$page_id = isset($input['page_id']) ? (int)$input['page_id'] : 0;
$title = trim($input['title'] ?? '');
$body = trim($input['body'] ?? '');
$status = trim($input['status'] ?? 'draft');
$allowed = ['draft', 'published', 'archived'];

if ($page_id <= 0 || $title === '' || $body === '' || !in_array($status, $allowed, true)) {
    json_error('Page id, title, body, and valid status are required.', 422);
}

try {
    $stmt = $conn->prepare("UPDATE content_pages
                            SET title = :title,
                                body = :body,
                                status = :status,
                                updated_by = :updated_by,
                                published_at = CASE
                                    WHEN :status_for_publish = 'published' AND published_at IS NULL THEN current_timestamp()
                                    WHEN :status_for_clear <> 'published' THEN NULL
                                    ELSE published_at
                                END
                            WHERE page_id = :page_id");
    $stmt->execute([
        ':title' => $title,
        ':body' => $body,
        ':status' => $status,
        ':updated_by' => current_admin_user_id(),
        ':status_for_publish' => $status,
        ':status_for_clear' => $status,
        ':page_id' => $page_id
    ]);

    json_success('Content page updated.', ['page_id' => $page_id]);
} catch (PDOException $e) {
    json_error('Unable to update content page.', 500);
}
?>
