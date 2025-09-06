<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loggedIn = isset($_SESSION['user_id']);
$avatarUrl = $loggedIn ? ($_SESSION['avatar_url'] ?? 'https://ui-avatars.com/api/?name=U&background=1e3a8a&color=fff') : null;
if (!isset($baseUrl)) {
    $baseUrl = 'http://localhost/Festivo/';
}
$year = date('Y');
?>
<footer class="bg-gradient-to-b from-slate-950 via-slate-950 to-slate-950 text-slate-300 border-t border-white/5 relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 18% 35%,#fbbf24 0,transparent 60%),radial-gradient(circle at 82% 70%,#6366f1 0,transparent 55%),linear-gradient(120deg,rgba(255,255,255,.04),rgba(0,0,0,0));"></div>
    <div class="relative max-w-11/12 mx-auto px-6 py-20">
        <div class="grid gap-16 md:gap-20 lg:gap-24 md:grid-cols-2 lg:grid-cols-12">
            <!-- Brand / Mission -->
            <div class="space-y-5 lg:col-span-3">
                <a href="<?php echo $baseUrl; ?>" class="text-2xl font-semibold tracking-wide bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent">Festivo</a>
                <p class="text-sm leading-relaxed text-slate-400 max-w-xs">Crafting memorable experiences through seamlessly curated events, powered by community and creativity.</p>
                <div class="flex gap-3 text-lg pt-1">
                    <a href="#" class="hover:text-amber-400 transition" aria-label="Twitter">🐦</a>
                    <a href="#" class="hover:text-amber-400 transition" aria-label="Instagram">📸</a>
                    <a href="#" class="hover:text-amber-400 transition" aria-label="Facebook">📘</a>
                    <a href="#" class="hover:text-amber-400 transition" aria-label="YouTube">▶️</a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4 text-sm lg:col-span-2">
                <h3 class="uppercase tracking-wider text-[11px] font-semibold text-slate-400">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo $baseUrl; ?>" class="hover:text-white/90">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>events.php" class="hover:text-white/90">Events</a></li>
                    <li><a href="<?php echo $baseUrl; ?>about.php" class="hover:text-white/90">About</a></li>
                    <?php if ($loggedIn): ?><li><a href="<?php echo $baseUrl; ?>dashboard.php" class="hover:text-amber-400">Dashboard</a></li><?php endif; ?>
                </ul>
            </div>

            <!-- Resources -->
            <div class="space-y-4 text-sm lg:col-span-2">
                <h3 class="uppercase tracking-wider text-[11px] font-semibold text-slate-400">Resources</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-white/90">Support</a></li>
                    <li><a href="#" class="hover:text-white/90">FAQ</a></li>
                    <li><a href="#" class="hover:text-white/90">Blog</a></li>
                    <!-- <li><a href="#" class="hover:text-white/90">Media Kit</a></li> -->
                    <!-- <li><a href="#" class="hover:text-white/90">Community</a></li> -->
                </ul>
            </div>

            <!-- Newsletter (account removed) -->
            <div class="space-y-5 text-sm lg:col-span-5">
                <h3 class="uppercase tracking-wider text-[11px] font-semibold text-slate-400">Stay Updated</h3>
                <p class="text-slate-400 leading-relaxed">Get curated event highlights & early access announcements. No spam—just vibes.</p>
                <form action="<?php echo $baseUrl; ?>subscribe.php" method="post" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="email" name="email" required placeholder="you@example.com" class="input input-sm w-full bg-slate-900/40 border-slate-700 focus:border-amber-400 focus:outline-none" />
                        <button type="submit" class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Join</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-20 pt-8 border-t border-white/10 flex flex-col md:flex-row gap-4 md:items-center justify-between text-[11px] text-slate-500">
            <div class="flex flex-wrap gap-4 md:order-2">
                <a href="#" class="hover:text-slate-300">Terms</a>
                <a href="#" class="hover:text-slate-300">Privacy</a>
                <a href="#" class="hover:text-slate-300">Cookies</a>
                <a href="#" class="hover:text-slate-300">Status</a>
            </div>
            <div class="md:order-1">© <?php echo $year; ?> Festivo. Built with balance (φ ≈ 1.618).</div>
        </div>
    </div>
</footer>
<!-- Global scripts could go here -->
</body>
</html>