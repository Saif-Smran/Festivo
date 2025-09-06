<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
require_once __DIR__.'/dbConnect.php';
$uid = (int)($_SESSION['user_id'] ?? 0);

// Detect events schema
$hasStatus = false; if($r = $conn->query("SHOW COLUMNS FROM events LIKE 'status'")){ $hasStatus = $r->num_rows>0; $r->close(); }
$creatorCol = null; foreach(['created_by','organizer_id','user_id'] as $cand){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $creatorCol=$cand; $r->close(); break; }
    $r->close();
  }
}
$creatorCol = $creatorCol ?: 'user_id';

// Detect participants table
$participantTable = null; foreach(['event_participants','EventParticipants'] as $cand){
  if($r=$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $participantTable=$cand; $r->close(); break; }
    $r->close();
  }
}

// Counts
$upcomingCount = 0; $myEventsCount=0; $participationsCount=0;
if($q = $conn->query("SELECT COUNT(*) c FROM events WHERE start_time >= NOW()".($hasStatus?" AND status='published'":""))){ $upcomingCount=(int)$q->fetch_assoc()['c']; $q->close(); }
if($q = $conn->query("SELECT COUNT(*) c FROM events WHERE $creatorCol = $uid")){ $myEventsCount=(int)$q->fetch_assoc()['c']; $q->close(); }
if($participantTable){ if($q=$conn->query("SELECT COUNT(*) c FROM $participantTable WHERE user_id = $uid")){ $participationsCount=(int)$q->fetch_assoc()['c']; $q->close(); } }

// Recent lists
$recentMy=[]; if($q = $conn->query("SELECT event_id,title,start_time FROM events WHERE $creatorCol=$uid ORDER BY start_time DESC LIMIT 5")){
  while($row=$q->fetch_assoc()){ $recentMy[]=$row; } $q->close();
}
$recentJoined=[]; if($participantTable){
  $sql = "SELECT e.event_id,e.title,e.start_time FROM $participantTable p JOIN events e ON e.event_id=p.event_id WHERE p.user_id=$uid ORDER BY e.start_time DESC LIMIT 5";
  if($q=$conn->query($sql)){ while($row=$q->fetch_assoc()){ $recentJoined[]=$row; } $q->close(); }
}
include __DIR__.'/navbar.php';
?>
<main class="min-h-screen bg-slate-50">
  <div class="max-w-11/12 mx-auto px-6 pt-24 pb-16">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
      <aside class="md:col-span-3">
        <div class="sticky top-24">
          <nav class="space-y-1 text-sm">
            <a href="<?php echo $baseUrl; ?>dashboard.php" class="block px-3 py-2 rounded-md bg-slate-900 text-slate-100">Overview</a>
            <a href="<?php echo $baseUrl; ?>dashboard_create.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Create Event</a>
            <a href="<?php echo $baseUrl; ?>dashboard_my_events.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">My Created Events</a>
            <a href="<?php echo $baseUrl; ?>dashboard_participated.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Participated Events</a>
          </nav>
        </div>
      </aside>
      <section class="md:col-span-9 space-y-6">
        <h1 class="text-2xl font-semibold text-slate-900">Overview</h1>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-sm text-slate-500">Upcoming Events</div>
            <div class="text-2xl font-semibold text-slate-900"><?php echo (int)$upcomingCount; ?></div>
          </div>
          <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-sm text-slate-500">My Events</div>
            <div class="text-2xl font-semibold text-slate-900"><?php echo (int)$myEventsCount; ?></div>
          </div>
          <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-sm text-slate-500">Participations</div>
            <div class="text-2xl font-semibold text-slate-900"><?php echo (int)$participationsCount; ?></div>
          </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-5">
          <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-medium mb-3">My recent events</h2>
            <?php if(count($recentMy)): ?>
              <ul class="divide-y divide-slate-100">
                <?php foreach($recentMy as $e): ?>
                  <li class="py-2 flex items-center justify-between gap-3">
                    <div>
                      <a class="font-medium text-slate-900 hover:underline" href="<?php echo $baseUrl; ?>event.php?id=<?php echo (int)$e['event_id']; ?>"><?php echo htmlspecialchars($e['title']); ?></a>
                      <div class="text-xs text-slate-500"><?php echo date('M d, Y H:i', strtotime($e['start_time'])); ?></div>
                    </div>
                    <a class="btn btn-xs" href="<?php echo $baseUrl; ?>dashboard_edit_event.php?id=<?php echo (int)$e['event_id']; ?>">Edit</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="text-sm text-slate-600">No events yet.</div>
            <?php endif; ?>
          </div>
          <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-medium mb-3">Recent participations</h2>
            <?php if(count($recentJoined)): ?>
              <ul class="divide-y divide-slate-100">
                <?php foreach($recentJoined as $e): ?>
                  <li class="py-2 flex items-center justify-between gap-3">
                    <div>
                      <a class="font-medium text-slate-900 hover:underline" href="<?php echo $baseUrl; ?>event.php?id=<?php echo (int)$e['event_id']; ?>"><?php echo htmlspecialchars($e['title']); ?></a>
                      <div class="text-xs text-slate-500"><?php echo date('M d, Y H:i', strtotime($e['start_time'])); ?></div>
                    </div>
                    <a class="btn btn-xs" href="<?php echo $baseUrl; ?>event.php?id=<?php echo (int)$e['event_id']; ?>">View</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="text-sm text-slate-600">No participations yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
