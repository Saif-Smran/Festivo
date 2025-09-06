<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
include __DIR__.'/navbar.php';
$uid = (int)$_SESSION['user_id'];
// Find participant table by candidates
$participantTable = null; foreach(['event_participants','EventParticipants'] as $cand){ $r=$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($cand)."'"); if($r && $r->num_rows){ $participantTable=$cand; $r->close(); break; } if($r) $r->close(); }
$res = null;
if ($participantTable){
  $sql = "SELECT e.event_id,e.title,e.category,e.location,e.start_time,e.end_time FROM events e JOIN $participantTable ep ON e.event_id=ep.event_id WHERE ep.user_id=$uid ORDER BY e.start_time DESC";
  $res = $conn->query($sql);
}
?>
<main class="min-h-screen bg-slate-50">
  <div class="max-w-11/12 mx-auto px-6 pt-24 pb-16">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
      <aside class="md:col-span-3">
        <div class="sticky top-24">
          <nav class="space-y-1 text-sm">
            <a href="<?php echo $baseUrl; ?>dashboard.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Overview</a>
            <a href="<?php echo $baseUrl; ?>dashboard_create.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Create Event</a>
            <a href="<?php echo $baseUrl; ?>dashboard_my_events.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">My Created Events</a>
            <a href="<?php echo $baseUrl; ?>dashboard_participated.php" class="block px-3 py-2 rounded-md bg-slate-900 text-slate-100">Participated Events</a>
          </nav>
        </div>
      </aside>
      <section class="md:col-span-9 space-y-6">
        <h1 class="text-2xl font-semibold text-slate-900">Participated Events</h1>
        <div class="grid gap-4">
          <?php if($res && $res->num_rows): while($row=$res->fetch_assoc()): ?>
            <div class="rounded-lg border border-slate-200 bg-white p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($row['category']); ?> • <?php echo htmlspecialchars($row['location']); ?> • <?php echo date('M d, Y H:i', strtotime($row['start_time'])); ?></div>
              </div>
              <a class="text-xs text-amber-600 hover:text-amber-500" href="<?php echo $baseUrl; ?>event.php?id=<?php echo (int)$row['event_id']; ?>">View</a>
            </div>
          <?php endwhile; else: ?>
            <div class="text-sm text-slate-600">No participations yet.</div>
          <?php endif; $res && $res->free(); ?>
        </div>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
