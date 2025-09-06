<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
include __DIR__.'/navbar.php';
$uid = (int)$_SESSION['user_id'];
// Fetch events created by user (best-effort based on schema)
$hasStatus=false; $hasCapacity=false; $creatorCol=null;
if ($r = $conn->query("SHOW COLUMNS FROM events LIKE 'status'")) { $hasStatus = $r->num_rows>0; $r->close(); }
if ($r = $conn->query("SHOW COLUMNS FROM events LIKE 'capacity'")) { $hasCapacity = $r->num_rows>0; $r->close(); }
foreach(['created_by','organizer_id','user_id'] as $cand){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
    $r->close();
  }
}
$creatorCol = $creatorCol ?: 'user_id';
$res = $conn->query("SELECT event_id,title,category,location,start_time,end_time".($hasStatus?",status":"")." FROM events WHERE $creatorCol={$uid} ORDER BY start_time DESC");
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
        <h1 class="text-2xl font-semibold text-slate-900">My Created Events</h1>
        <?php if(isset($_SESSION['flash_events'])): $f=$_SESSION['flash_events']; unset($_SESSION['flash_events']); ?>
          <div class="text-sm <?php echo $f['type']==='success'?'text-emerald-600':'text-rose-600'; ?>"><?php echo htmlspecialchars($f['text']); ?></div>
        <?php endif; ?>
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
          <table class="table table-sm">
            <thead>
              <tr class="text-slate-600">
                <th>Title</th>
                <th class="hidden sm:table-cell">Category</th>
                <th class="hidden md:table-cell">Location</th>
                <th>Starts</th>
                <?php if($hasStatus): ?><th>Status</th><?php endif; ?>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if($res && $res->num_rows): while($row=$res->fetch_assoc()): ?>
                <tr>
                  <td class="font-medium text-slate-900"><?php echo htmlspecialchars($row['title']); ?></td>
                  <td class="hidden sm:table-cell"><?php echo htmlspecialchars($row['category']); ?></td>
                  <td class="hidden md:table-cell"><?php echo htmlspecialchars($row['location']); ?></td>
                  <td><?php echo date('M d, Y H:i', strtotime($row['start_time'])); ?></td>
                  <?php if($hasStatus): ?><td><span class="<?php echo $row['status']==='published'?'text-emerald-600':'text-slate-500'; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td><?php endif; ?>
                  <td class="text-right">
                    <a class="btn btn-xs" href="<?php echo $baseUrl; ?>event.php?id=<?php echo (int)$row['event_id']; ?>">View</a>
                    <a class="btn btn-xs" href="<?php echo $baseUrl; ?>dashboard_event_participants.php?event_id=<?php echo (int)$row['event_id']; ?>">Participants</a>
                    <a class="btn btn-xs" href="<?php echo $baseUrl; ?>dashboard_edit_event.php?id=<?php echo (int)$row['event_id']; ?>">Edit</a>
                    <form action="<?php echo $baseUrl; ?>dashboard_delete_event.php" method="post" class="inline" onsubmit="return confirm('Delete this event?');">
                      <input type="hidden" name="id" value="<?php echo (int)$row['event_id']; ?>" />
                      <button class="btn btn-xs btn-error text-white" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-sm text-slate-600">No events yet.</td></tr>
              <?php endif; $res && $res->free(); ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
