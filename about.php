<?php
// session_start();
$baseUrl = 'http://localhost/Festivo/';
include 'navbar.php';
?>
<main class="min-h-screen bg-slate-50">
  <section class="relative overflow-hidden pt-24 pb-20 bg-gradient-to-b from-slate-900 via-slate-900/95 to-slate-900 text-slate-100">
    <div class="absolute inset-0 opacity-25" style="background-image:radial-gradient(circle at 25% 35%,#fbbf24 0,transparent 60%),radial-gradient(circle at 75% 70%,#6366f1 0,transparent 55%)"></div>
    <div class="max-w-5xl mx-auto px-6">
      <h1 class="text-4xl md:text-5xl font-semibold tracking-tight bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-400 bg-clip-text text-transparent mb-8">About Festivo</h1>
      <p class="text-slate-300 text-lg leading-relaxed max-w-3xl">Festivo is a lightweight event management platform focused on clarity, speed, and community participation. We help creators publish events and attendees discover meaningful experiences—without the bloat.</p>
      <div class="grid md:grid-cols-3 gap-10 mt-14">
        <div class="space-y-3">
          <h2 class="text-xl font-semibold text-white">Our Mission</h2>
          <p class="text-slate-400 text-sm leading-relaxed">Empower people to create, share, and attend events that build real-world connections.</p>
        </div>
        <div class="space-y-3">
          <h2 class="text-xl font-semibold text-white">Principles</h2>
          <ul class="text-slate-400 text-sm leading-relaxed space-y-2 list-disc list-inside">
            <li>Fast & intuitive UX</li>
            <li>Transparent participation</li>
            <li>Scalable data design</li>
            <li>Modular evolution</li>
          </ul>
        </div>
        <div class="space-y-3">
          <h2 class="text-xl font-semibold text-white">Tech Stack</h2>
          <ul class="text-slate-400 text-sm leading-relaxed space-y-2 list-disc list-inside">
            <li>PHP (procedural start)</li>
            <li>MySQL (InnoDB)</li>
            <li>Tailwind + DaisyUI</li>
            <li>Progressive enhancements</li>
          </ul>
        </div>
      </div>
      <div class="mt-16 grid md:grid-cols-2 gap-12">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-white">Roadmap Highlights</h3>
          <ul class="text-slate-400 text-sm leading-relaxed space-y-2 list-disc list-inside">
            <li>User profiles & avatars</li>
            <li>Ticket tiers & RSVPs</li>
            <li>Event media galleries</li>
            <li>Full-text event search</li>
            <li>Admin analytics dashboard</li>
          </ul>
        </div>
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-white">Contact & Support</h3>
          <p class="text-slate-400 text-sm leading-relaxed">Have suggestions or need help? Reach out via the support page (coming soon) or open a feedback ticket once the portal is live.</p>
          <div class="flex gap-3 pt-2">
            <a href="<?php echo $baseUrl; ?>events.php" class="btn btn-sm bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Browse Events</a>
            <a href="<?php echo isset($_SESSION['user_id']) ? $baseUrl.'dashboard.php' : $baseUrl.'register.php'; ?>" class="btn btn-sm btn-outline border-amber-400 text-amber-300 hover:bg-amber-500/10 hover:border-amber-300">Create Event</a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="py-20">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-3 gap-10">
      <div class="p-6 rounded-xl bg-white shadow border border-slate-200">
        <h4 class="font-semibold text-slate-800 mb-2">Lightweight Core</h4>
        <p class="text-slate-500 text-sm leading-relaxed">A minimal schema keeps performance high and complexity low while leaving room to scale.</p>
      </div>
      <div class="p-6 rounded-xl bg-white shadow border border-slate-200">
        <h4 class="font-semibold text-slate-800 mb-2">Flexible Growth</h4>
        <p class="text-slate-500 text-sm leading-relaxed">Add modules like tickets, venues, media, or messaging as needs evolve.</p>
      </div>
      <div class="p-6 rounded-xl bg-white shadow border border-slate-200">
        <h4 class="font-semibold text-slate-800 mb-2">Creator First</h4>
        <p class="text-slate-500 text-sm leading-relaxed">Designed so event creators can launch quickly and iterate with feedback.</p>
      </div>
    </div>
  </section>
</main>
<?php include 'footer.php'; ?>
