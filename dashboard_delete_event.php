<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($id<=0){ $_SESSION['flash_events']=['type'=>'error','text'=>'Invalid event.']; header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Detect creator column
$creatorCol=null; foreach(['created_by','organizer_id','user_id'] as $cand){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
    $r->close();
  }
}
$creatorCol = $creatorCol ?: 'user_id';

// Only delete if owned by user
$stmt = $conn->prepare("DELETE FROM events WHERE event_id=? AND $creatorCol=? LIMIT 1");
$stmt->bind_param('ii', $id, $uid);
if($stmt->execute() && $stmt->affected_rows>0){
  $_SESSION['flash_events']=['type'=>'success','text'=>'Event deleted.'];
} else {
  $_SESSION['flash_events']=['type'=>'error','text'=>'Event not found or not yours.'];
}
$stmt->close();
header('Location: '.$baseUrl.'dashboard_my_events.php');
exit;
