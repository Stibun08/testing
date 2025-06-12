<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    exit;
}

$conn = db_connect();
// Use the correct session key - adjust this based on what you're storing in your login
$user_id = $_SESSION["user_id"]; // Make sure this matches what you store during login
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

switch($action) {
    case 'get_boards':
        $stmt = mysqli_prepare($conn, "SELECT * FROM boards WHERE user_id = ? ORDER BY created_at");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        echo json_encode(mysqli_fetch_all($result, MYSQLI_ASSOC));
        mysqli_stmt_close($stmt);
        break;
        
    case 'create_board':
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $stmt = mysqli_prepare($conn, "INSERT INTO boards (user_id, name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $name);
        $result = mysqli_stmt_execute($stmt);
        $insert_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        echo json_encode(['id' => $insert_id, 'success' => $result]);
        break;
        
    case 'get_board_data':
        $board_id = (int)$_GET['board_id'];
        
        // First verify the board belongs to the user
        $stmt = mysqli_prepare($conn, "SELECT id FROM boards WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $board_id, $user_id);
        mysqli_stmt_execute($stmt);
        $board_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($board_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        // Get lists
        $stmt = mysqli_prepare($conn, "SELECT * FROM lists WHERE board_id = ? ORDER BY position");
        mysqli_stmt_bind_param($stmt, "i", $board_id);
        mysqli_stmt_execute($stmt);
        $lists_result = mysqli_stmt_get_result($stmt);
        $lists = mysqli_fetch_all($lists_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        // Get cards for each list
        foreach($lists as &$list) {
            $list_id = $list['id'];
            $stmt = mysqli_prepare($conn, "SELECT * FROM cards WHERE list_id = ? ORDER BY position");
            mysqli_stmt_bind_param($stmt, "i", $list_id);
            mysqli_stmt_execute($stmt);
            $cards_result = mysqli_stmt_get_result($stmt);
            $list['cards'] = mysqli_fetch_all($cards_result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
        }
        
        echo json_encode($lists);
        break;
        
    case 'create_list':
        $board_id = (int)$_POST['board_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);
        $position = (int)$_POST['position'];
        
        // Verify board ownership
        $stmt = mysqli_prepare($conn, "SELECT id FROM boards WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $board_id, $user_id);
        mysqli_stmt_execute($stmt);
        $board_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($board_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "INSERT INTO lists (board_id, title, color, position) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issi", $board_id, $title, $color, $position);
        $result = mysqli_stmt_execute($stmt);
        $insert_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        echo json_encode(['id' => $insert_id, 'success' => $result]);
        break;
        
    case 'update_list':
        $list_id = (int)$_POST['list_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        
        // Verify list ownership through board
        $stmt = mysqli_prepare($conn, "SELECT l.id FROM lists l JOIN boards b ON l.board_id = b.id WHERE l.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $list_id, $user_id);
        mysqli_stmt_execute($stmt);
        $list_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($list_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE lists SET title = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $title, $list_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $result]);
        break;
        
    case 'delete_list':
        $list_id = (int)$_POST['list_id'];
        
        // Verify list ownership through board
        $stmt = mysqli_prepare($conn, "SELECT l.id FROM lists l JOIN boards b ON l.board_id = b.id WHERE l.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $list_id, $user_id);
        mysqli_stmt_execute($stmt);
        $list_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($list_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "DELETE FROM lists WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $list_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $result]);
        break;
        
    case 'create_card':
        $list_id = (int)$_POST['list_id'];
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);
        $position = (int)$_POST['position'];
        
        // Verify card ownership through list and board
        $stmt = mysqli_prepare($conn, "SELECT l.id FROM lists l JOIN boards b ON l.board_id = b.id WHERE l.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $list_id, $user_id);
        mysqli_stmt_execute($stmt);
        $list_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($list_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "INSERT INTO cards (list_id, content, color, position) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issi", $list_id, $content, $color, $position);
        $result = mysqli_stmt_execute($stmt);
        $insert_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        echo json_encode(['id' => $insert_id, 'success' => $result]);
        break;
        
    case 'update_card':
        $card_id = (int)$_POST['card_id'];
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        
        // Verify card ownership through list and board
        $stmt = mysqli_prepare($conn, "SELECT c.id FROM cards c JOIN lists l ON c.list_id = l.id JOIN boards b ON l.board_id = b.id WHERE c.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $card_id, $user_id);
        mysqli_stmt_execute($stmt);
        $card_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($card_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE cards SET content = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $content, $card_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $result]);
        break;
        
    case 'move_card':
        $card_id = (int)$_POST['card_id'];
        $new_list_id = (int)$_POST['new_list_id'];
        $position = (int)$_POST['position'];
        
        // Verify both card and target list ownership
        $stmt = mysqli_prepare($conn, "SELECT c.id FROM cards c JOIN lists l ON c.list_id = l.id JOIN boards b ON l.board_id = b.id WHERE c.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $card_id, $user_id);
        mysqli_stmt_execute($stmt);
        $card_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        $stmt = mysqli_prepare($conn, "SELECT l.id FROM lists l JOIN boards b ON l.board_id = b.id WHERE l.id = ? AND b.user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $new_list_id, $user_id);
        mysqli_stmt_execute($stmt);
        $list_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if (mysqli_num_rows($card_result) === 0 || mysqli_num_rows($list_result) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            break;
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE cards SET list_id = ?, position = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "iii", $new_list_id, $position, $card_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $result]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

mysqli_close($conn);
?>