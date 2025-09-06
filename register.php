<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$baseUrl = 'http://localhost/Festivo/';
require_once __DIR__.'/dbConnect.php';
// Decide hashing algorithm (prefer Argon2id if supported)
$hashAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
$hashOptions = $hashAlgo === PASSWORD_DEFAULT ? [] : ['memory_cost'=>1<<17,'time_cost'=>4,'threads'=>2];

// Map users table columns dynamically to tolerate schema differences
function festivo_map_user_columns(mysqli $conn): array {
  $map = [
    'id' => null,
    'email' => null,
    'password' => null,
    'display' => null,
    'avatar' => null,
  ];
  $alts = [
    'id' => ['user_id','id'],
    'email' => ['email','email_address','user_email'],
    'password' => ['password_hash','password','pass_hash','password_digest'],
    'display' => ['display_name','name','full_name','username'],
    'avatar' => ['avatar_url','avatar','profile_image','profile_picture']
  ];
  if ($res = $conn->query('SHOW COLUMNS FROM users')) {
    $cols = [];
    while ($r = $res->fetch_assoc()) { $cols[strtolower($r['Field'])] = $r; }
    $res->close();
    foreach ($alts as $key => $cands) {
      foreach ($cands as $c) {
        if (isset($cols[strtolower($c)])) { $map[$key] = $c; break; }
      }
    }
  }
  return $map;
}
$userCols = festivo_map_user_columns($conn);

$errors = []; $success = null;
if (isset($_SESSION['user_id'])) { $success = 'Already registered & logged in.'; }

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='register') {
  $display_name = trim($_POST['display_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password_confirm'] ?? '';
  if ($display_name==='') $errors[] = 'Display name required.';
  if ($email==='') $errors[] = 'Email required.';
  elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
  if ($password==='') $errors[] = 'Password required.';
  elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($password !== $password2) $errors[] = 'Passwords do not match.';
  if (!$errors) {
    // Email uniqueness if email column exists
    if (!empty($userCols['email'])) {
      $checkSql = "SELECT 1 FROM users WHERE `{$userCols['email']}`=? LIMIT 1";
      $stmt = $conn->prepare($checkSql);
      if ($stmt) {
        $stmt->bind_param('s',$email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) { $errors[]='Email already registered.'; }
        $stmt->close();
      } else { $errors[]='Registration unavailable.'; }
    } else {
      $errors[] = 'Registration unavailable (email column missing).';
    }
  }
  if (!$errors) {
    $hash = password_hash($password, $hashAlgo, $hashOptions);
    // Build dynamic insert
    $cols = [];
    $vals = [];
    $types = '';
    if (!empty($userCols['display'])) { $cols[] = "`{$userCols['display']}`"; $vals[] = $display_name; $types .= 's'; }
    $cols[] = "`{$userCols['email']}`"; $vals[] = $email; $types .= 's';
    if (empty($userCols['password'])) { $errors[] = 'Registration unavailable (password column missing).'; }
    $cols[] = "`{$userCols['password']}`"; $vals[] = $hash; $types .= 's';
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql = 'INSERT INTO users ('.implode(',', $cols).') VALUES ('.$placeholders.')';
    $ins = $conn->prepare($sql);
    if ($ins) {
      $ins->bind_param($types, ...$vals);
      if ($ins->execute()) {
        $uid = $ins->insert_id;
        $_SESSION['user_id'] = (int)$uid;
        $_SESSION['display_name'] = $display_name ?: explode('@',$email)[0];
        $success = 'Account created successfully!';
        header('Location: '.$baseUrl.'events.php');
        exit;
      } else { $errors[] = 'Failed to create account.'; }
      $ins->close();
    } else { $errors[]='Registration unavailable.'; }
  }
}

include ('navbar.php');
?>
<main class="pt-28 pb-24 bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 text-slate-100 min-h-screen relative overflow-hidden">
  <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 22% 32%,#fbbf24 0,transparent 60%),radial-gradient(circle at 78% 72%,#6366f1 0,transparent 55%);"></div>
  <div class="relative max-w-5xl mx-auto px-6">
    <div class="flex items-center justify-center px-4 pt-28 pb-16 min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 text-slate-100 relative overflow-hidden">
      <div class="pointer-events-none absolute inset-0 opacity-[0.05]" style="background-image:radial-gradient(circle at 22% 32%,#fbbf24 0,transparent 60%),radial-gradient(circle at 78% 72%,#6366f1 0,transparent 55%);"></div>
      <div class="relative w-full max-w-lg">
        <div class="rounded-2xl bg-slate-900/70 backdrop-blur-md border border-white/10 shadow-xl shadow-black/40 overflow-hidden">
          <div class="px-8 pt-8 pb-6 space-y-7">
            <div class="space-y-2 text-center">
              <h1 class="text-3xl font-semibold tracking-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent">Create Account</h1>
              <p class="text-slate-400 text-sm">Join Festivo to discover and participate in events.</p>
              <?php if($success && !$errors): ?><div class="text-xs text-emerald-400"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            </div>
          <?php if($errors): ?>
            <div class="text-xs space-y-1 bg-rose-500/10 border border-rose-400/30 rounded-md p-3">
              <?php foreach($errors as $e): ?><div class="text-rose-300 flex items-start gap-2">⚠️ <span><?php echo htmlspecialchars($e); ?></span></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="post" class="space-y-5" novalidate>
  <input type="hidden" name="action" value="register" />
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wide text-slate-400">Display Name</label><br>
          <input type="text" name="display_name" required maxlength="100" class="input input-sm w-full bg-slate-800/60 border-slate-700 focus:border-amber-400 focus:outline-none" placeholder="Jane Doe" value="<?php echo htmlspecialchars($_POST['display_name'] ?? '', ENT_QUOTES); ?>" />
        </div>
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wide text-slate-400">Email</label><br>
          <input type="email" name="email" required autocomplete="email" class="input input-sm w-full bg-slate-800/60 border-slate-700 focus:border-amber-400 focus:outline-none" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" />
        </div>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-xs uppercase tracking-wide text-slate-400">Password</label><br>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="input input-md w-full bg-slate-800/60 border-slate-700 focus:border-amber-400 focus:outline-none tracking-widest" placeholder="Min 8 characters" />
          </div>
          <div class="space-y-2">
            <label class="text-xs uppercase tracking-wide text-slate-400">Confirm Password</label><br>
            <input id="passwordConfirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password" class="input input-md w-full bg-slate-800/60 border-slate-700 focus:border-amber-400 focus:outline-none tracking-widest" placeholder="Repeat password" />
          </div>
          <div class="flex items-center justify-between text-[11px] text-slate-500">
            <div id="strength" class="flex-1"></div>
            <button type="button" id="toggleRegisterPassword" class="text-amber-400 hover:text-amber-300">Show</button>
          </div>
        </div>
        <button type="submit" class="btn w-full bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold shadow-lg shadow-amber-500/20">Create Account</button>
        <p class="text-[11px] text-center text-slate-500">Already have an account? <a href="<?php echo $baseUrl; ?>login.php" class="text-amber-400 hover:text-amber-300">Login</a></p>
      </form>
        <ul class="text-[11px] text-slate-500 space-y-1 pt-1">
          <li>Use a strong unique password (12+ chars recommended).</li>
          <li>We never store plain-text passwords.</li>
          <li><?php echo $hashAlgo===PASSWORD_DEFAULT? 'Secure hashing enabled.' : 'Argon2id hashing enabled.'; ?></li>
        </ul>
      </div>
      <div class="order-1 lg:order-2 hidden lg:flex flex-col gap-6 pl-4">
        <div class="space-y-4">
          <h2 class="text-xl font-medium text-slate-200">Why Create an Account?</h2>
          <ul class="text-slate-400 text-sm space-y-2 list-disc list-inside">
            <li>Register for events instantly</li>
            <li>Track all your participations</li>
            <li>Receive upcoming event alerts</li>
          </ul>
        </div>
        <div class="mt-4 p-4 rounded-lg bg-slate-900/40 border border-white/10 text-[11px] text-slate-500 leading-relaxed">
          Your password is hashed with modern algorithms before storage.
        </div>
      </div>
    </div>
  </div>
</main>
<script>
  (function(){
    const p = document.getElementById('password');
    const c = document.getElementById('passwordConfirm');
    const strength = document.getElementById('strength');
    const toggle = document.getElementById('toggleRegisterPassword');
    function score(v){
      let s = 0; if(v.length>=8) s++; if(v.length>=12) s++; if(/[A-Z]/.test(v)) s++; if(/[0-9]/.test(v)) s++; if(/[^A-Za-z0-9]/.test(v)) s++; return s; }
    function render(){
      const v = p.value; const sc = score(v); const match = c.value && v===c.value; const colors=['rose','orange','amber','yellow','emerald','emerald'];
      let label='Weak'; if(sc>=2) label='Fair'; if(sc>=3) label='Good'; if(sc>=4) label='Strong'; if(sc>=5) label='Excellent';
      strength.innerHTML = v? `<span class="text-${colors[sc]}-400">${label}${match? ' ✓':''}</span>`: '';
    }
    [p,c].forEach(el=> el && el.addEventListener('input',render));
    if(toggle){
      toggle.addEventListener('click',()=>{
        const show = p.type==='password';
        p.type = show?'text':'password';
        c.type = show?'text':'password';
        toggle.textContent = show? 'Hide':'Show';
      });
    }
  })();
</script>
<?php include __DIR__.'/footer.php'; ?>
<?php $conn && $conn->close(); ?>
