<?php
// session_start();
// DB connection (adjust credentials if different)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'festivo';
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$dbError = null;
if ($mysqli->connect_errno) { $dbError = 'Database connection failed'; }

// Detect optional columns
$hasStatus = false; $hasCapacity = false; $hasDescription = true; // description assumed present
if (!$dbError) {
    if ($r = $mysqli->query("SHOW COLUMNS FROM events LIKE 'status'")) { $hasStatus = $r->num_rows > 0; $r->free(); }
    if ($r = $mysqli->query("SHOW COLUMNS FROM events LIKE 'capacity'")) { $hasCapacity = $r->num_rows > 0; $r->free(); }
}

// Filters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$validCategories = ['conference','wedding','concert','birthday','workshop','festival','other'];
if ($category && !in_array(strtolower($category), $validCategories)) { $category = ''; }

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$events = [];
$total = 0;
if (!$dbError) {
    $where = [];
    $where[] = 'start_time >= NOW()';
    if ($hasStatus) { $where[] = "status='published'"; }
    if ($category) { $safeCat = $mysqli->real_escape_string($category); $where[] = "category='".$safeCat."'"; }
    $whereSql = implode(' AND ', $where);

    // Count
    $countSql = "SELECT COUNT(*) as c FROM events WHERE $whereSql";
    if ($cr = $mysqli->query($countSql)) { $total = (int)$cr->fetch_assoc()['c']; $cr->free(); }

    $cols = 'event_id,title,category,location,start_time,end_time';
    if ($hasDescription) { $cols .= ',description'; }
    if ($hasCapacity) { $cols .= ',capacity'; }
    if ($hasStatus) { $cols .= ',status'; }

    $sql = "SELECT $cols FROM events WHERE $whereSql ORDER BY start_time ASC LIMIT $perPage OFFSET $offset";
    if ($res = $mysqli->query($sql)) {
        while ($row = $res->fetch_assoc()) { $events[] = $row; }
        $res->free();
    } else { $dbError = 'Query failed: '.$mysqli->error; }
}
$totalPages = $total ? (int)ceil($total / $perPage) : 1;

include 'navbar.php';
?>
<main class="relative isolate min-h-screen bg-slate-50">
  <section class="pt-20 pb-12 bg-gradient-to-b from-slate-900 via-slate-900/95 to-slate-900 text-slate-100 relative overflow-hidden">
    <div class="absolute inset-0 opacity-25" style="background-image:radial-gradient(circle at 20% 35%,#fbbf24 0,transparent 60%),radial-gradient(circle at 80% 70%,#6366f1 0,transparent 55%)"></div>
    <div class="max-w-11/12 mx-auto px-6 flex flex-col lg:flex-row gap-14">
      <div class="flex-1 space-y-6">
        <h1 class="text-4xl md:text-5xl font-semibold tracking-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent">Explore Upcoming Events</h1>
        <p class="text-slate-300 max-w-xl">Find workshops, concerts, conferences and more. Filter by category and join what resonates.</p>
        <form method="get" class="flex flex-wrap gap-3 items-center text-sm">
          <select name="category" class="select select-sm bg-slate-800/40 border-slate-600 text-slate-200">
            <option value="">All Categories</option>
            <?php foreach($validCategories as $cat): ?>
              <option value="<?php echo $cat; ?>" <?php echo $category===$cat?'selected':''; ?>><?php echo ucfirst($cat); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold" type="submit">Filter</button>
          <?php if($category): ?><a href="events.php" class="btn btn-sm btn-ghost text-slate-300">Reset</a><?php endif; ?>
        </form>
        <div class="text-xs text-slate-400 pt-1">
          Showing <?php echo count($events); ?> of <?php echo $total; ?> upcoming events<?php echo $category? ' in '.ucfirst($category):''; ?>.
        </div>
      </div>
      <div class="flex-1 hidden lg:block">
        <div class="rounded-2xl border border-white/10 bg-slate-800/40 p-6 h-full flex flex-col justify-between">
          <p class="text-xs uppercase tracking-wider text-amber-300">Why Festivo</p>
          <ul class="space-y-3 text-sm text-slate-300">
            <li>• Seamless participation tracking</li>
            <li>• Flexible event categories</li>
            <li>• Scales with your community</li>
            <li>• Built for performance & clarity</li>
          </ul>
          <div class="text-[11px] text-slate-500">Powered by a lean MySQL schema.</div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-16">
    <div class="max-w-11/12 mx-auto px-6">
      <?php if($dbError): ?>
        <div class="alert alert-error max-w-xl mb-10"><span><?php echo htmlspecialchars($dbError); ?></span></div>
      <?php elseif(!$total): ?>
        <div class="p-10 rounded-xl border border-dashed border-slate-300 bg-white text-center text-slate-500">No events found. <?php if(isset($_SESSION['user_id'])): ?>Create one from your <a href="dashboard.php" class="text-amber-600 hover:underline">dashboard</a>.<?php else: ?><a href="register.php" class="text-amber-600 hover:underline">Register</a> to publish the first event.<?php endif; ?></div>
      <?php else: ?>
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach($events as $e): 
            $start = date('M d, Y H:i', strtotime($e['start_time']));
            $end = date('H:i', strtotime($e['end_time']));
            $cat = ucfirst($e['category']);
            $url = 'event.php?id='.(int)$e['event_id'];
            $desc = isset($e['description']) ? trim($e['description']) : '';
            if($desc !== ''){ $snippet = strip_tags($desc); $snippet = function_exists('mb_substr')? mb_substr($snippet,0,100): substr($snippet,0,100); if(strlen($desc)>100){ $snippet.='…'; } } else { $snippet=''; }
            $capacityInfo = '';
            if($hasCapacity && isset($e['capacity']) && $e['capacity']) { $capacityInfo = (int)$e['capacity'].' cap'; }
          ?>
          <a href="<?php echo $url; ?>" class="group block rounded-xl bg-white shadow-sm hover:shadow-md transition border border-slate-200 overflow-hidden relative">
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition bg-gradient-to-br from-amber-400/10 to-indigo-400/10 pointer-events-none"></div>
            <div class="p-5 flex flex-col gap-4 h-full">
              <div class="flex items-center justify-between text-[11px] tracking-wide uppercase font-medium text-slate-500">
                <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 group-hover:bg-amber-400/20 group-hover:text-amber-700 transition"><?php echo htmlspecialchars($cat); ?></span>
                <span class="text-slate-400"><?php echo $start; ?></span>
              </div>
              <h2 class="text-slate-800 font-semibold leading-snug line-clamp-2 group-hover:text-slate-900"><?php echo htmlspecialchars($e['title']); ?></h2>
              <?php if($snippet): ?><p class="text-xs text-slate-500 line-clamp-3"><?php echo htmlspecialchars($snippet); ?></p><?php endif; ?>
              <div class="mt-auto flex items-center justify-between pt-1 text-[11px] text-slate-500">
                <span class="flex items-center gap-1">📍 <?php echo htmlspecialchars($e['location'] ?? 'TBA'); ?></span>
                <span class="flex items-center gap-2">
                  <?php if($capacityInfo): ?><span><?php echo $capacityInfo; ?></span><?php endif; ?>
                  <span class="text-slate-400">→ <?php echo $end; ?></span>
                </span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php if($totalPages > 1): ?>
          <div class="flex flex-wrap items-center gap-2 justify-center mt-14 text-sm">
            <?php for($i=1;$i<=$totalPages;$i++): $isCurrent = $i===$page; $qs = http_build_query(array_filter(['page'=>$i,'category'=>$category])); ?>
              <a href="?<?php echo $qs; ?>" class="px-3 py-1.5 rounded-md border <?php echo $isCurrent? 'bg-amber-500 border-amber-500 text-slate-900 font-semibold':'border-slate-300 text-slate-600 hover:border-amber-400 hover:text-amber-600'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php include 'footer.php'; ?>
<?php if(isset($mysqli) && $mysqli instanceof mysqli){ $mysqli->close(); } ?>
