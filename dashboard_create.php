<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.$baseUrl.'login.php'); exit; }
$error = null; $success = null;

// Detect optional columns in events: capacity, status, and created_by/organizer fields
$hasCapacity = false; $hasStatus=false; $creatorCol=null;
if($conn){
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE 'capacity'")){ $hasCapacity = $r->num_rows>0; $r->close(); }
  if($r = $conn->query("SHOW COLUMNS FROM events LIKE 'status'")){ $hasStatus = $r->num_rows>0; $r->close(); }
  // Support either created_by or organizer_id
  foreach(['created_by','organizer_id','user_id'] as $cand){
    if($r = $conn->query("SHOW COLUMNS FROM events LIKE '".$conn->real_escape_string($cand)."'")){
      if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
      $r->close();
    }
  }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = trim($_POST['title'] ?? '');
  $category = trim($_POST['category'] ?? 'other');
  $location = trim($_POST['location'] ?? '');
  $start = $_POST['start_time'] ?? '';
  $end = $_POST['end_time'] ?? '';
  $description = trim($_POST['description'] ?? '');
  $capacity = isset($_POST['capacity']) && $_POST['capacity']!=='' ? (int)$_POST['capacity'] : null;
  $status = $_POST['status'] ?? 'published';

  // Normalize datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
  $normalizeDt = function($dt){
    $dt = str_replace('T',' ', trim($dt));
    if($dt && strlen($dt) === 16){ $dt .= ':00'; }
    return $dt;
  };
  $startN = $normalizeDt($start);
  $endN = $normalizeDt($end);

  if($title===''){ $error = 'Title is required.'; }
  elseif($startN==='' || $endN===''){ $error = 'Start and End time are required.'; }
  elseif(strtotime($endN) <= strtotime($startN)){ $error = 'End must be after start.'; }
  else{
    // Build dynamic insert
    $cols = ['title','description','category','location','start_time','end_time'];
    $place = ['?','?','?','?','?','?'];
    $types = 'ssssss';
    $values = [$title,$description,$category,$location,$startN,$endN];
    $includeCapacity = $hasCapacity && $capacity !== null;
    if($includeCapacity){ $cols[]='capacity'; $place[]='?'; $types.='i'; $values[] = $capacity; }
    if($hasStatus){ $cols[]='status'; $place[]='?'; $types.='s'; $values[] = $status; }
    if($creatorCol){ $cols[]=$creatorCol; $place[]='?'; $types.='i'; $values[] = (int)$_SESSION['user_id']; }

    $sql = 'INSERT INTO events ('.implode(',', $cols).') VALUES ('.implode(',', $place).')';
    if($stmt = $conn->prepare($sql)){
      $stmt->bind_param($types, ...$values);
      if($stmt->execute()){
        $newId = $stmt->insert_id ?: $conn->insert_id;
        $stmt->close();
        $_SESSION['flash_events'] = ['type'=>'success','text'=>'Event created successfully.'];
        header('Location: '.$baseUrl.'dashboard_my_events.php');
        exit;
      }else{
        $error = 'Could not save event.';
      }
      $stmt->close();
    }else{
      $error = 'Failed to prepare save statement.';
    }
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
            <a href="<?php echo $baseUrl; ?>dashboard_create.php" class="block px-3 py-2 rounded-md bg-slate-900 text-slate-100">Create Event</a>
            <a href="<?php echo $baseUrl; ?>dashboard_my_events.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">My Created Events</a>
            <a href="<?php echo $baseUrl; ?>dashboard_participated.php" class="block px-3 py-2 rounded-md hover:bg-slate-200/60">Participated Events</a>
          </nav>
        </div>
      </aside>
      <section class="md:col-span-9 space-y-6">
        <h1 class="text-2xl font-semibold text-slate-900">Create Event</h1>
    <?php if($error): ?><div class="alert alert-error text-sm mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Title</label>
      <input name="title" class="input input-sm w-full bg-white border-slate-300" required />
            </div>
            <div>
              <label class="text-xs text-slate-500">Category</label>
              <select name="category" class="select select-sm w-full bg-white border-slate-300">
                <option>conference</option><option>wedding</option><option>concert</option><option>birthday</option><option>workshop</option><option>festival</option><option>other</option>
              </select>
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Location</label>
      <input name="location" class="input input-sm w-full bg-white border-slate-300" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-slate-500">Start</label>
                <input type="datetime-local" name="start_time" class="input input-sm w-full bg-white border-slate-300" required />
              </div>
              <div>
                <label class="text-xs text-slate-500">End</label>
                <input type="datetime-local" name="end_time" class="input input-sm w-full bg-white border-slate-300" required />
              </div>
            </div>
          </div>
          <div>
            <label class="text-xs text-slate-500">Description</label>
            <textarea name="description" class="textarea textarea-sm w-full bg-white border-slate-300" rows="5"></textarea>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-slate-500">Capacity (optional)</label>
              <input type="number" name="capacity" class="input input-sm w-full bg-white border-slate-300" />
            </div>
            <div>
              <label class="text-xs text-slate-500">Status</label>
              <select name="status" class="select select-sm w-full bg-white border-slate-300">
                <option>published</option>
                <option>draft</option>
              </select>
            </div>
          </div>
          <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Save</button>
        </form>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
