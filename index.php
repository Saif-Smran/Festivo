<?php
// Basic DB connection (adjust credentials as needed)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'festivo';
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$dbError = null;
if ($mysqli->connect_errno) {
        $dbError = 'Database connection failed';
}

// Fetch top upcoming events (limit 6). Handle old schema without 'status' column.
$events = [];
if (!$dbError) {
    $hasStatus = false;
    if ($colCheck = $mysqli->query("SHOW COLUMNS FROM events LIKE 'status'")) {
        $hasStatus = $colCheck->num_rows > 0;
        $colCheck->free();
    }
    $selectCols = "event_id,title,description,category,location,start_time,end_time" . ($hasStatus ? ",status" : "");
    $where = "start_time >= NOW()" . ($hasStatus ? " AND status='published'" : "");
    $sql = "SELECT $selectCols FROM events WHERE $where ORDER BY start_time ASC LIMIT 6";
    if ($res = $mysqli->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $events[] = $row;
        }
        $res->free();
    } else {
        $dbError = 'Query failed: ' . $mysqli->error; // capture specific error
    }
}

include 'navbar.php';
?>
<main class="relative isolate">
    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900"></div>
        <div class="absolute inset-0 -z-10 opacity-30" style="background-image:radial-gradient(circle at 20% 30%,#fbbf24 0,transparent 55%),radial-gradient(circle at 80% 65%,#6366f1 0,transparent 55%);"></div>
        <div class="max-w-11/12 mx-auto px-6 pt-28 pb-32 flex flex-col lg:flex-row items-center gap-16">
            <div class="flex-1 space-y-7 text-center lg:text-left">
                <h1 class="text-4xl md:text-5xl font-semibold tracking-tight leading-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent">Discover & Shape Memorable Events</h1>
                <p class="text-slate-300 max-w-xl mx-auto lg:mx-0 text-lg">Browse curated upcoming experiences or create your own. Music, culture, workshops, and more—all in one place.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="events.php" class="btn bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Explore Events</a>
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="btn btn-outline border-amber-400 text-amber-300 hover:bg-amber-500/10 hover:border-amber-300">Create Event</a>
                </div>
                <div class="flex flex-wrap gap-6 pt-4 justify-center lg:justify-start text-sm text-slate-400">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Real-time schedule</div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-indigo-400"></span> Community-driven</div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Secure access</div>
                </div>
            </div>
            <div class="flex-1 w-full max-w-xl">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2 aspect-video rounded-xl bg-slate-800/50 backdrop-blur border border-white/5 overflow-hidden relative">
                        <div class="absolute inset-0 bg-gradient-to-tr from-amber-400/10 via-transparent to-indigo-400/10"></div>
                        <div class="p-5 h-full flex flex-col justify-between">
                            <p class="text-xs uppercase tracking-wider text-amber-300">Featured</p>
                            <p class="text-slate-300 text-sm leading-relaxed">Create and publish events with rich descriptions, capacity limits, and streamlined participation.</p>
                        </div>
                    </div>
                    <div class="aspect-square rounded-xl bg-slate-800/40 border border-white/5 flex items-center justify-center text-slate-400 text-xs">Your Event</div>
                    <div class="aspect-square rounded-xl bg-slate-800/40 border border-white/5 flex items-center justify-center text-slate-400 text-xs">Community</div>
                    <div class="col-span-2 aspect-[3/1] rounded-xl bg-slate-800/40 border border-white/5 flex items-center justify-center text-slate-400 text-xs">Engagement</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Top Events -->
    <section class="py-20 bg-slate-100/40 relative">
        <div class="max-w-11/12 mx-auto px-6">
            <div class="flex items-end justify-between flex-wrap gap-6 mb-10">
                <div>
                    <h2 class="text-2xl md:text-3xl font-semibold text-slate-800">Upcoming Events</h2>
                    <p class="text-slate-500 text-sm mt-1">Hand-picked from the next wave of published events.</p>
                </div>
                <a href="events.php" class="text-amber-600 hover:text-amber-500 font-medium text-sm">View all →</a>
            </div>
            <?php if($dbError): ?>
                <div class="alert alert-error max-w-xl">
                    <span>Could not load events. Configure database connection.</span>
                </div>
            <?php elseif(empty($events)): ?>
                <div class="p-8 rounded-xl border border-dashed border-slate-300 bg-white/60 text-center text-slate-500">No upcoming events yet. Be the first to <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="text-amber-600 hover:underline">create one</a>.</div>
            <?php else: ?>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach($events as $e): 
                        $start = date('M d, Y H:i', strtotime($e['start_time']));
                        $end = date('H:i', strtotime($e['end_time']));
                        $cat = ucfirst($e['category']);
                        $slugUrl = 'event.php?id='.(int)$e['event_id']; // slug column not in DB; using id param
                    ?>
                        <a href="<?php echo $slugUrl; ?>" class="group block rounded-xl bg-white shadow-sm hover:shadow-md transition border border-slate-200 overflow-hidden relative">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition bg-gradient-to-br from-amber-400/10 to-indigo-400/10 pointer-events-none"></div>
                            <div class="p-5 flex flex-col gap-4 h-full">
                                <div class="flex items-center justify-between text-[11px] tracking-wide uppercase font-medium text-slate-500">
                                    <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 group-hover:bg-amber-400/20 group-hover:text-amber-700 transition"><?php echo htmlspecialchars($cat); ?></span>
                                    <span class="text-slate-400"><?php echo $start; ?></span>
                                </div>
                                <h3 class="text-slate-800 font-semibold leading-snug line-clamp-2 group-hover:text-slate-900"><?php echo htmlspecialchars($e['title']); ?></h3>
                                <?php
                                $desc = isset($e['description']) ? trim($e['description']) : '';
                                if($desc !== '') {
                                    $snippet = strip_tags($desc);
                                    if(function_exists('mb_substr')) {
                                        $snippet = mb_substr($snippet,0,140);
                                    } else {
                                        $snippet = substr($snippet,0,140);
                                    }
                                    if(strlen($desc) > 140) { $snippet .= '…'; }
                                    echo '<p class="text-sm text-slate-500 line-clamp-3">'.htmlspecialchars($snippet).'</p>';
                                }
                                ?>
                                <div class="mt-auto flex items-center justify-between pt-2 text-xs text-slate-500">
                                    <span class="flex items-center gap-1">📍 <?php echo htmlspecialchars($e['location'] ?? 'TBA'); ?></span>
                                    <span class="text-slate-400">ends <?php echo $end; ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'footer.php'; ?>
<?php if(isset($mysqli) && $mysqli instanceof mysqli){ $mysqli->close(); } ?>