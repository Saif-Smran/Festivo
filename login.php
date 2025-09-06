<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
// Helper (future extensibility) – consistent hashing settings handled at registration

// Discover users table columns dynamically
function festivo_map_user_columns_login(mysqli $conn): array {
  $map = ['id'=>null,'email'=>null,'password'=>null,'display'=>null,'avatar'=>null];
  $alts = [
    'id' => ['user_id','id'],
    'email' => ['email','email_address','user_email'],
    'password' => ['password_hash','password','pass_hash','password_digest'],
    'display' => ['display_name','name','full_name','username'],
    'avatar' => ['avatar_url','avatar','profile_image','profile_picture']
  ];
  if ($res = $conn->query('SHOW COLUMNS FROM users')) {
    $cols = [];
    while ($r = $res->fetch_assoc()) { $cols[strtolower($r['Field'])] = true; }
    $res->close();
    foreach ($alts as $k=>$cands) { foreach($cands as $c){ if(isset($cols[strtolower($c)])){ $map[$k]=$c; break; } } }
  }
  return $map;
}
$userCols = festivo_map_user_columns_login($conn);

$errors = []; $notice = null;
if (isset($_SESSION['user_id'])) {
    $notice = 'Already logged in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='login') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($email === '' || $password === '') {
    $errors[] = 'Email and password required.';
  } else {
    if (empty($userCols['email']) || empty($userCols['password']) || empty($userCols['id'])) {
      $errors[] = 'Login unavailable.';
    } else {
      $selectCols = ["`{$userCols['id']}` AS uid","`{$userCols['email']}` AS uemail","`{$userCols['password']}` AS upass"];
      if (!empty($userCols['display'])) $selectCols[] = "`{$userCols['display']}` AS uname"; else $selectCols[] = "`{$userCols['email']}` AS uname";
      if (!empty($userCols['avatar'])) $selectCols[] = "`{$userCols['avatar']}` AS uavatar";
      $sql = 'SELECT '.implode(',', $selectCols).' FROM users WHERE `'.$userCols['email'].'`=? LIMIT 1';
      $stmt = $conn->prepare($sql);
    }
    if ($stmt) {
      $stmt->bind_param('s',$email);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['upass'])) {
          if (password_needs_rehash($user['upass'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare('UPDATE users SET `'.$userCols['password'].'`=? WHERE `'.$userCols['id'].'`=?');
            if ($up) { $up->bind_param('si',$newHash,$user['user_id']); $up->execute(); $up->close(); }
          }
          $_SESSION['user_id'] = (int)$user['uid'];
          $_SESSION['display_name'] = $user['uname'];
          if (isset($user['uavatar']) && !empty($user['uavatar'])) { $_SESSION['avatar_url'] = $user['uavatar']; }
          header('Location: '.$baseUrl.'events.php');
          exit;
        } else {
          $errors[] = 'Invalid credentials.';
        }
      } else {
        $errors[] = 'No account found for that email.';
      }
      $res && $res->free();
      $stmt->close();
    } else {
      $errors[] = 'Login unavailable.';
    }
  }
}

include __DIR__.'/navbar.php';
?>
<main class="flex items-center justify-center px-4 pt-28 pb-16 min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 text-slate-100 relative overflow-hidden">
  <div class="pointer-events-none absolute inset-0 opacity-[0.05]" style="background-image:radial-gradient(circle at 22% 32%,#fbbf24 0,transparent 60%),radial-gradient(circle at 78% 72%,#6366f1 0,transparent 55%);"></div>
  <div class="relative w-full max-w-md">
    <div class="rounded-2xl bg-slate-900/70 backdrop-blur-md border border-white/10 shadow-xl shadow-black/40 overflow-hidden">
      <div class="px-8 pt-8 pb-6 space-y-6">
        <div class="space-y-2 text-center">
          <h1 class="text-3xl font-semibold tracking-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent">Login</h1>
          <p class="text-slate-400 text-sm">Access your Festivo account.</p>
          <?php if($notice): ?><div class="text-xs text-emerald-400"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
        </div>
        <?php if($errors): ?>
          <div class="text-xs space-y-1 bg-rose-500/10 border border-rose-400/30 rounded-md p-3">
            <?php foreach($errors as $e): ?><div class="text-rose-300 flex items-start gap-2">⚠️ <span><?php echo htmlspecialchars($e); ?></span></div><?php endforeach; ?>
          </div>
        <?php endif; ?>
        <form method="post" class="space-y-5" novalidate>
          <input type="hidden" name="action" value="login" />
          <div class="space-y-1">
            <label class="block text-[11px] tracking-wide font-medium text-slate-300">Email</label><br>
            <div class="relative group">
              <input type="email" name="email" required autocomplete="email" class="peer w-full rounded-md bg-slate-800/60 border border-slate-700/70 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:outline-none px-4 h-11 text-sm placeholder-slate-500" placeholder="you@example.com" />
              <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-500 text-xs group-focus-within:text-amber-400">✉️</span>
            </div>
          </div>
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label class="block text-[11px] tracking-wide font-medium text-slate-300">Password</label><br>
              <button type="button" id="togglePassword" class="text-[11px] text-amber-400 hover:text-amber-300">Show</button>
            </div>
            <div class="relative group">
              <input id="passwordField" type="password" name="password" required autocomplete="current-password" class="peer w-full rounded-md bg-slate-800/60 border border-slate-700/70 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:outline-none px-4 h-11 text-sm tracking-widest placeholder-slate-500" placeholder="••••••••" />
              <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-500 text-xs group-focus-within:text-amber-400">🔒</span>
            </div>
          </div>
          <button type="submit" class="w-full h-11 rounded-md font-semibold text-slate-900 bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 hover:from-amber-300 hover:via-amber-400 hover:to-yellow-400 transition shadow-lg shadow-amber-500/25 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400 ring-offset-slate-900">Login</button>
          <p class="text-[11px] text-center text-slate-500">No account? <a href="<?php echo $baseUrl; ?>register.php" class="text-amber-400 hover:text-amber-300 font-medium">Register</a></p>
        </form>
      </div>
    </div>
  </div>
</main>
<script>
  (function(){
    const btn = document.getElementById('togglePassword');
    const field = document.getElementById('passwordField');
    if(btn && field){
      btn.addEventListener('click',()=>{
        const show = field.type === 'password';
        field.type = show ? 'text':'password';
        btn.textContent = show ? 'Hide':'Show';
      });
    }
  })();
</script>
<?php include __DIR__.'/footer.php'; ?>
<?php $conn && $conn->close(); ?>
