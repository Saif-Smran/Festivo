<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';

// DB connection (adjust as needed)
$dbHost = 'localhost'; $dbUser = 'root'; $dbPass = ''; $dbName = 'festivo';
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$dbError = $mysqli->connect_errno ? 'Database connection failed' : null;

$event = null; $joined = false; $joinMessage = null; $joinError = null; $participantCount = 0; $capacity = null; $hasStatus=false; $hasCapacity=false; $status='published'; $creatorCol=null; $ownerId=null;

// Detect optional columns & participant table name
$participantTable = null; 
$participantTableCandidates = ['event_participants','EventParticipants'];
if(!$dbError){
  foreach($participantTableCandidates as $cand){
    $check = $mysqli->query("SHOW TABLES LIKE '".$mysqli->real_escape_string($cand)."'");
    if($check && $check->num_rows){ $participantTable = $cand; $check->close(); break; }
    if($check) $check->close();
  }
  if($r = $mysqli->query("SHOW COLUMNS FROM events LIKE 'status'")){ $hasStatus = $r->num_rows>0; $r->close(); }
  if($r = $mysqli->query("SHOW COLUMNS FROM events LIKE 'capacity'")){ $hasCapacity = $r->num_rows>0; $r->close(); }
  // Detect creator column
  foreach(['created_by','organizer_id','user_id'] as $cand){
    if($r = $mysqli->query("SHOW COLUMNS FROM events LIKE '".$mysqli->real_escape_string($cand)."'")){
      if($r->num_rows>0){ $creatorCol = $cand; $r->close(); break; }
      $r->close();
    }
  }
}

// CSRF removed per request; relying on session + server validation

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($eventId <=0){ $dbError = $dbError ?: 'Invalid event id.'; }

if(!$dbError){
  $cols = 'event_id,title,description,category,location,start_time,end_time';
  if($hasCapacity) $cols .= ',capacity';
  if($hasStatus) $cols .= ',status';
  if($creatorCol) $cols .= ", $creatorCol AS owner_id";
  $sql = "SELECT $cols FROM events WHERE event_id=? LIMIT 1";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param('i',$eventId);
    if($stmt->execute()){
      $res = $stmt->get_result();
      if($res && $res->num_rows){ $event = $res->fetch_assoc(); }
      $res && $res->free();
    }
    $stmt->close();
  }
  if($event){
    $status = $hasStatus ? $event['status'] : 'published';
    if($hasCapacity && !empty($event['capacity'])){ $capacity = (int)$event['capacity']; }
  if(isset($event['owner_id'])){ $ownerId = (int)$event['owner_id']; }
    // Count participants if table exists
    if($participantTable){
      $countSql = "SELECT COUNT(*) c FROM $participantTable WHERE event_id=".$eventId;
      if($rc = $mysqli->query($countSql)){ $participantCount = (int)$rc->fetch_assoc()['c']; $rc->close(); }
    }
    // Already joined?
    if(isset($_SESSION['user_id']) && $participantTable){
      $uid = (int)$_SESSION['user_id'];
      $chk = $mysqli->prepare("SELECT 1 FROM $participantTable WHERE event_id=? AND user_id=? LIMIT 1");
      if($chk){ $chk->bind_param('ii',$eventId,$uid); $chk->execute(); $chk->store_result(); $joined = $chk->num_rows>0; $chk->close(); }
    }
  }
}

// Handle join
if($event && isset($_POST['action']) && $_POST['action']==='join' && isset($_SESSION['user_id'])){
  if($ownerId !== null && (int)$_SESSION['user_id'] === (int)$ownerId){
    $_SESSION['flash_join'] = ['type'=>'error','text'=>'Organizers cannot participate in their own events.'];
    header('Location: '.$baseUrl.'event.php?id='.$eventId);
    exit;
  }
  if($participantTable===null){
  $_SESSION['flash_join'] = ['type'=>'error','text'=>'Participation unavailable.'];
  }elseif($joined){
  $_SESSION['flash_join'] = ['type'=>'success','text'=>'Already participating.'];
  }elseif($status!=='published'){
  $_SESSION['flash_join'] = ['type'=>'error','text'=>'Event not open for participation.'];
  }elseif($capacity !== null && $participantCount >= $capacity){
  $_SESSION['flash_join'] = ['type'=>'error','text'=>'Event is full.'];
  }else{
    $uid = (int)$_SESSION['user_id'];
    $ins = $mysqli->prepare("INSERT INTO $participantTable (event_id,user_id) VALUES (?,?)");
    if($ins){
      $ins->bind_param('ii', $eventId, $uid);
      if($ins->execute()){
    $_SESSION['flash_join'] = ['type'=>'success','text'=>'You are now participating!'];
      }else{
        $err = $ins->errno ?: $mysqli->errno;
    if($err == 1062){ $_SESSION['flash_join'] = ['type'=>'success','text'=>'Already participating.']; } else { $_SESSION['flash_join'] = ['type'=>'error','text'=>'Join failed.']; }
      }
      $ins->close();
    }
  }
  header('Location: '.$baseUrl.'event.php?id='.$eventId);
  exit;
}

include 'navbar.php';
?>
<main class="bg-slate-50">
  <section class="pt-24 pb-16 bg-gradient-to-b from-slate-900 via-slate-900/95 to-slate-900 text-slate-100 relative overflow-hidden">
    <div class="absolute inset-0 opacity-25 pointer-events-none select-none" aria-hidden="true" style="background-image:radial-gradient(circle at 25% 35%,#fbbf24 0,transparent 60%),radial-gradient(circle at 75% 70%,#6366f1 0,transparent 55%)"></div>
    <div class="max-w-5xl mx-auto px-6 relative z-10">
      <?php if($dbError): ?>
        <div class="alert alert-error mb-8"><span><?php echo htmlspecialchars($dbError); ?></span></div>
      <?php elseif(!$event): ?>
        <div class="p-10 rounded-xl border border-dashed border-slate-300 bg-white text-center text-slate-600">Event not found.</div>
      <?php else: ?>
        <?php if(isset($_SESSION['flash_join'])): $f=$_SESSION['flash_join']; unset($_SESSION['flash_join']); ?>
          <div class="mb-4 text-sm <?php echo $f['type']==='success' ? 'text-emerald-400' : 'text-rose-400'; ?>"><?php echo htmlspecialchars($f['text']); ?></div>
        <?php endif; ?>
        <div class="flex flex-col gap-8">
          <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-4 text-xs uppercase tracking-wide text-slate-400">
              <span class="px-2 py-1 rounded-md bg-slate-800/60 border border-white/10 text-slate-200"><?php echo htmlspecialchars(ucfirst($event['category'])); ?></span>
              <span><?php echo date('M d, Y H:i', strtotime($event['start_time'])); ?> → <?php echo date('H:i', strtotime($event['end_time'])); ?></span>
              <?php if($capacity!==null): ?><span><?php echo $participantCount; ?>/<?php echo $capacity; ?> attending</span><?php endif; ?>
              <?php if($hasStatus): ?><span class="<?php echo $status==='published' ? 'text-emerald-400':'text-slate-500'; ?>">Status: <?php echo htmlspecialchars($status); ?></span><?php endif; ?>
            </div>
            <h1 class="text-3xl md:text-4xl font-semibold tracking-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent"><?php echo htmlspecialchars($event['title']); ?></h1>
            <?php if(!empty($event['location'])): ?><p class="text-slate-300 text-sm flex items-center gap-2">📍 <span><?php echo htmlspecialchars($event['location']); ?></span></p><?php endif; ?>
          </div>
          <div class="grid md:grid-cols-3 gap-10">
            <div class="md:col-span-2 space-y-6">
              <div class="prose prose-invert max-w-none">
                <p class="text-slate-300 leading-relaxed whitespace-pre-line"><?php echo nl2br(htmlspecialchars($event['description'] ?? 'No description provided.')); ?></p>
              </div>
            </div>
            <aside class="space-y-5">
              <div class="rounded-xl bg-slate-800/60 border border-white/10 p-5 space-y-4">
                <h2 class="text-sm font-semibold tracking-wide text-slate-200">Participation</h2>
                <?php if(!isset($_SESSION['user_id'])): ?>
                  <p class="text-xs text-slate-400">Login or create an account to participate.</p>
                  <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold flex-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400" aria-label="Login to your account to participate">Login</a>
                    <a href="<?php echo $baseUrl; ?>register.php" class="btn btn-sm btn-outline border-amber-400 text-amber-300 hover:bg-amber-500/10 hover:border-amber-300 flex-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400" aria-label="Register a new account to participate">Register</a>
                  </div>
                <?php else: ?>
                  <?php if($joinMessage): ?><div class="text-xs text-emerald-400"><?php echo htmlspecialchars($joinMessage); ?></div><?php endif; ?>
                  <?php if($joinError): ?><div class="text-xs text-rose-400"><?php echo htmlspecialchars($joinError); ?></div><?php endif; ?>
                  <?php if($joined): ?>
                    <button class="btn btn-sm w-full bg-emerald-600 border-none hover:bg-emerald-500" disabled>Participating ✓</button>
                  <?php elseif($ownerId !== null && (int)$_SESSION['user_id'] === (int)$ownerId): ?>
                    <button class="btn btn-sm w-full bg-slate-600 border-none" disabled>Organizer</button>
                  <?php elseif($status!=='published'): ?>
                    <button class="btn btn-sm w-full bg-slate-600 border-none" disabled>Not Open</button>
                  <?php elseif($capacity!==null && $participantCount >= $capacity): ?>
                    <button class="btn btn-sm w-full bg-slate-600 border-none" disabled>Full</button>
                  <?php else: ?>
                    <form method="post" class="space-y-2">
                      <input type="hidden" name="action" value="join" />
                      <button type="submit" class="btn btn-sm w-full bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Participate</button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <div class="rounded-xl bg-slate-800/40 border border-white/10 p-5 space-y-3 text-xs text-slate-400">
                <p><strong class="text-slate-300 font-medium">Starts:</strong> <?php echo date('r', strtotime($event['start_time'])); ?></p>
                <p><strong class="text-slate-300 font-medium">Ends:</strong> <?php echo date('r', strtotime($event['end_time'])); ?></p>
                <p><strong class="text-slate-300 font-medium">Duration:</strong> <?php 
                  $dur = (strtotime($event['end_time']) - strtotime($event['start_time']))/3600; 
                  echo number_format($dur,2).' hrs'; ?>
                </p>
                <?php if($participantTable): ?><p><strong class="text-slate-300 font-medium">Participants:</strong> <?php echo $participantCount; ?><?php echo $capacity? '/'.$capacity:''; ?></p><?php endif; ?>
                <p class="pt-2 text-[11px] opacity-60">Event ID: <?php echo $eventId; ?></p>
              </div>
            </aside>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php include 'footer.php'; ?>
<?php if(isset($mysqli) && $mysqli instanceof mysqli){ $mysqli->close(); } ?>
