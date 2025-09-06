<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if($eventId<=0){ header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Detect creator column and ensure ownership
$creatorCol=null; foreach(['created_by','organizer_id','user_id'] as $cand){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
    $r->close();
  }
}
$creatorCol = $creatorCol ?: 'user_id';

$owns = false; $st = $conn->prepare("SELECT 1 FROM events WHERE event_id=? AND $creatorCol=? LIMIT 1");
$st->bind_param('ii', $eventId, $uid); $st->execute(); $st->store_result(); $owns = $st->num_rows>0; $st->close();
if(!$owns){ $_SESSION['flash_events']=['type'=>'error','text'=>'Not authorized to view participants for this event.']; header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Detect participant table and user display columns
$participantTable=null; foreach(['event_participants','EventParticipants'] as $cand){
  if($r=$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $participantTable=$cand; $r->close(); break; }
    $r->close();
  }
}
if(!$participantTable){ $_SESSION['flash_events']=['type'=>'error','text'=>'Participants table not found.']; header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Detect users table fields
$usersCols=['id'=>null,'email'=>null,'display'=>null,'avatar'=>null];
$map=['id'=>['id','user_id'],'email'=>['email','mail'],'display'=>['display_name','name','full_name','username'],'avatar'=>['avatar','profile_image','photo']];
foreach($map as $k=>$cands){
  foreach($cands as $cand){
    if($r=$conn->query("SHOW COLUMNS FROM users LIKE '".$conn->real_escape_string($cand)."'")){
      if($r->num_rows>0){ $usersCols[$k]=$cand; $r->close(); break; }
      $r->close();
    }
  }
}
if(!$usersCols['id']){ $_SESSION['flash_events']=['type'=>'error','text'=>'Users table schema not supported.']; header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Query participants joined with users
$uId=$usersCols['id']; $uEmail=$usersCols['email'] ?: 'NULL as email';
$uDisp = $usersCols['display'] ? $usersCols['display'] : null;
$uAv = $usersCols['avatar'] ? $usersCols['avatar'] : null;
$select = "p.user_id, u.$uId as uid";
if($uDisp){ $select .= ", u.$uDisp as display"; }
$select .= ", u.".($usersCols['email'] ?: $uId)." as email"; // fallback to id if no email
if($uAv){ $select .= ", u.$uAv as avatar"; }
$sql = "SELECT $select FROM $participantTable p JOIN users u ON u.$uId=p.user_id WHERE p.event_id=? ORDER BY u.$uId DESC";
$st = $conn->prepare($sql);
$st->bind_param('i',$eventId); $st->execute(); $rows = $st->get_result();

include __DIR__.'/navbar.php';
?>
<main class="min-h-screen bg-slate-50">
  <div class="max-w-11/12 mx-auto px-6 pt-24 pb-16">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
      <aside class="md:col-span-3">
        <div class="sticky top-24">
          <nav class="space-y-1 text-sm">
            <a href="<?php echo $baseUrl; ?>dashboard.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Overview</a>
            <a href="<?php echo $baseUrl; ?>dashboard_create.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Create Event</a>
            <a href="<?php echo $baseUrl; ?>dashboard_my_events.php" class="block px-3 py-2 rounded-md bg-slate-900 text-slate-100">My Created Events</a>
            <a href="<?php echo $baseUrl; ?>dashboard_participated.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Participated Events</a>
          </nav>
        </div>
      </aside>
      <section class="md:col-span-9 space-y-6">
        <h1 class="text-2xl font-semibold text-slate-900">Participants</h1>
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
          <table class="table table-sm">
            <thead>
              <tr class="text-slate-600">
                <th>User</th>
                <th>Email/ID</th>
              </tr>
            </thead>
            <tbody>
              <?php if($rows && $rows->num_rows): while($row=$rows->fetch_assoc()): ?>
                <tr>
                  <td class="flex items-center gap-2">
                    <?php if(isset($row['avatar']) && $row['avatar']): ?>
                      <img src="<?php echo htmlspecialchars($row['avatar']); ?>" alt="avatar" class="w-6 h-6 rounded-full" />
                    <?php else: ?>
                      <div class="w-6 h-6 rounded-full bg-slate-300"></div>
                    <?php endif; ?>
                    <span class="font-medium text-slate-900"><?php echo htmlspecialchars($row['display'] ?? ('User #'.$row['uid'])); ?></span>
                  </td>
                  <td class="text-slate-600"><?php echo htmlspecialchars($row['email'] ?? (string)$row['uid']); ?></td>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="2" class="text-sm text-slate-600">No participants yet.</td></tr>
              <?php endif; $rows && $rows->free(); ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
