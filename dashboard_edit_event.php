<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

// Detect creator column
$creatorCol=null; foreach(['created_by','organizer_id','user_id'] as $cand){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
    if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
    $r->close();
  }
}
$creatorCol = $creatorCol ?: 'user_id';

$hasCapacity = false; if($r=$conn->query("SHOW COLUMNS FROM events LIKE 'capacity'")){ $hasCapacity=$r->num_rows>0; $r->close(); }
$hasStatus = false; if($r=$conn->query("SHOW COLUMNS FROM events LIKE 'status'")){ $hasStatus=$r->num_rows>0; $r->close(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id<=0){ header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

// Load event owned by user
$cols = 'event_id,title,description,category,location,start_time,end_time';
if($hasCapacity) $cols .= ',capacity';
if($hasStatus) $cols .= ',status';
$stmt = $conn->prepare("SELECT $cols FROM events WHERE event_id=? AND $creatorCol=? LIMIT 1");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$res = $stmt->get_result();
$event = $res && $res->num_rows ? $res->fetch_assoc() : null;
$res && $res->free(); $stmt->close();
if(!$event){ $_SESSION['flash_events']=['type'=>'error','text'=>'Event not found or not yours.']; header('Location: '.$baseUrl.'dashboard_my_events.php'); exit; }

$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = trim($_POST['title'] ?? '');
  $category = trim($_POST['category'] ?? 'other');
  $location = trim($_POST['location'] ?? '');
  $start = $_POST['start_time'] ?? '';
  $end = $_POST['end_time'] ?? '';
  $description = trim($_POST['description'] ?? '');
  $capacityVal = isset($_POST['capacity']) && $_POST['capacity']!=='' ? (int)$_POST['capacity'] : null;
  $statusVal = $_POST['status'] ?? ($event['status'] ?? 'published');

  $normalizeDt = function($dt){ $dt=str_replace('T',' ',trim($dt)); if($dt && strlen($dt)===16){ $dt.=':00'; } return $dt; };
  $startN = $normalizeDt($start); $endN=$normalizeDt($end);
  if($title===''){ $error='Title is required.'; }
  elseif($startN===''||$endN===''){ $error='Start and End time are required.'; }
  elseif(strtotime($endN) <= strtotime($startN)){ $error='End must be after start.'; }
  else{
    $sets=['title=?','description=?','category=?','location=?','start_time=?','end_time=?'];
    $types='ssssss'; $vals=[$title,$description,$category,$location,$startN,$endN];
    if($hasCapacity){ $sets[]='capacity=?'; $types.='i'; $vals[]=$capacityVal; }
    if($hasStatus){ $sets[]='status=?'; $types.='s'; $vals[]=$statusVal; }
    $types.='i'; $vals[]=$id;
    $sql='UPDATE events SET '.implode(',', $sets).' WHERE event_id=? AND '.$creatorCol.'='.$uid;
    if($st=$conn->prepare($sql)){
      $st->bind_param($types, ...$vals);
      if($st->execute()){
        $_SESSION['flash_events']=['type'=>'success','text'=>'Event updated.'];
        header('Location: '.$baseUrl.'dashboard_my_events.php'); exit;
      } else { $error='Could not update event.'; }
      $st->close();
    } else { $error='Failed to prepare update statement.'; }
  }
}

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
        <h1 class="text-2xl font-semibold text-slate-900">Edit Event</h1>
        <?php if($error): ?><div class="alert alert-error text-sm mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Title</label>
              <input name="title" class="input input-sm w-full bg-white border-slate-300" required value="<?php echo htmlspecialchars($event['title']); ?>" />
            </div>
            <div>
              <label class="text-xs text-slate-500">Category</label>
              <select name="category" class="select select-sm w-full bg-white border-slate-300">
                <?php foreach(['conference','wedding','concert','birthday','workshop','festival','other'] as $opt): ?>
                  <option <?php echo $event['category']===$opt?'selected':''; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Location</label>
              <input name="location" class="input input-sm w-full bg-white border-slate-300" value="<?php echo htmlspecialchars($event['location']); ?>" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-slate-500">Start</label>
                <input type="datetime-local" name="start_time" class="input input-sm w-full bg-white border-slate-300" required value="<?php echo date('Y-m-d\TH:i', strtotime($event['start_time'])); ?>" />
              </div>
              <div>
                <label class="text-xs text-slate-500">End</label>
                <input type="datetime-local" name="end_time" class="input input-sm w-full bg-white border-slate-300" required value="<?php echo date('Y-m-d\TH:i', strtotime($event['end_time'])); ?>" />
              </div>
            </div>
          </div>
          <div>
            <label class="text-xs text-slate-500">Description</label>
            <textarea name="description" class="textarea textarea-sm w-full bg-white border-slate-300" rows="5"><?php echo htmlspecialchars($event['description']); ?></textarea>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Capacity (optional)</label>
              <input type="number" name="capacity" class="input input-sm w-full bg-white border-slate-300" value="<?php echo isset($event['capacity']) ? (int)$event['capacity'] : ''; ?>" />
            </div>
            <div>
              <label class="text-xs text-slate-500">Status</label>
              <select name="status" class="select select-sm w-full bg-white border-slate-300">
                <option <?php echo ($event['status'] ?? 'published')==='published'?'selected':''; ?>>published</option>
                <option <?php echo ($event['status'] ?? 'draft')==='draft'?'selected':''; ?>>draft</option>
              </select>
            </div>
          </div>
          <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Update</button>
        </form>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
