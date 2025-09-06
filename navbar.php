<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Simulate login status for demo if not set. In real app, remove these demo lines.
// $_SESSION['user_id'] = 123; // uncomment to simulate logged in
// $_SESSION['avatar_url'] = 'https://ui-avatars.com/api/?name=User&background=0f172a&color=fff';
$loggedIn = isset($_SESSION['user_id']);
$avatarUrl = $loggedIn ? ($_SESSION['avatar_url'] ?? 'https://ui-avatars.com/api/?name=U&background=1e3a8a&color=fff') : null;
// Base URL configuration (ensure trailing slash)
$baseUrl = 'http://localhost/Festivo/';
// Current request path (no query)
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
// Normalize potential Windows backslashes just in case
$currentPath = str_replace('\\', '/', $currentPath);
// Helper flags for active states
$isHome = ($currentPath === '/Festivo/' || $currentPath === '/Festivo/index.php');
$isEvents = str_ends_with($currentPath, '/events.php');
$isAbout = str_ends_with($currentPath, '/about.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Festivo</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        :root {
            --phi: 1.618;
        }

        /* Golden ratio based scale */
        .phi-gap {
            gap: calc(1rem * var(--phi));
        }

        .nav-underline a {
            position: relative;
        }

        .nav-underline a:after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -0.55rem;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #3b82f6);
            transition: width .35s cubic-bezier(.4, 0, .2, 1);
        }

        .nav-underline a:hover:after,
        .nav-underline a.active:after {
            width: 100%;
        }

        .glass-dark {
            backdrop-filter: blur(12px);
            background: linear-gradient(135deg, rgba(10, 18, 38, .92), rgba(15, 23, 42, .78));
        }

        .brand-gradient {
            background: linear-gradient(90deg, #fbbf24, #f59e0b 45%, #facc15);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .avatar-ring {
            box-shadow: 0 0 0 2px #1e293b, 0 0 0 4px rgba(234, 179, 8, .35);
        }

        /* Logged-in only responsive collapse */
        <?php if ($loggedIn): ?>@media (max-width:860px) {
            .mobile-hide {
                display: none !important;
            }

            #navLinks {
                display: none;
                flex-direction: column;
                align-items: center;
                width: 100%;
                padding: calc(.75rem * var(--phi)) 0 .75rem;
                margin-top: .5rem;
                border-top: 1px solid rgba(255, 255, 255, .07);
            }

            #navLinks.show {
                display: flex;
            }

            #hamburger {
                display: flex;
            }
        }

        <?php endif; ?>#hamburger {
            display: none;
            cursor: pointer;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .07);
            transition: .35s;
        }

        #hamburger:hover {
            background: rgba(255, 255, 255, .09);
        }

        #hamburger span {
            width: 22px;
            height: 2px;
            background: #f8fafc;
            border-radius: 2px;
            transition: .4s;
            margin-left: 10px;
        }

        #hamburger.active span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }

        #hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        #hamburger.active span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-100/40">
    <header class="sticky top-0 z-50 shadow-lg shadow-slate-900/20">
        <nav class="glass-dark text-slate-100/90 border-b border-white/5">
            <div class="max-w-11/12 mx-auto w-full px-6 grid items-center" style="grid-template-columns:auto 1fr auto; min-height:calc(2.5rem * var(--phi));">
                <div class="flex items-center gap-3">
                    <?php if ($loggedIn): ?>
                        <button id="hamburger" aria-label="Menu" aria-expanded="false" type="button" class="mr-2">
                            <span></span><span></span><span></span>
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $baseUrl; ?>" class="text-xl font-semibold tracking-wide brand-gradient select-none">Festivo</a>
                </div>
                <div id="navLinks" class="nav-underline hidden md:flex items-center justify-center gap-10 <?php echo $loggedIn ? '' : 'mobile-hide'; ?>">
                    <a href="<?php echo $baseUrl; ?>" class="<?php echo $isHome ? 'active font-medium text-white' : 'text-slate-200'; ?>">Home</a>
                    <a href="<?php echo $baseUrl; ?>events.php" class="<?php echo $isEvents ? 'active font-medium text-white' : 'text-slate-200'; ?>">Events</a>
                    <a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $isAbout ? 'active font-medium text-white' : 'text-slate-200'; ?>">About</a>
                </div>
                <div class="flex items-center justify-end gap-4">
                    <?php if ($loggedIn): ?>
                        <div class="relative">
                            <button id="avatarBtn" type="button" class="relative group" aria-haspopup="menu" aria-expanded="false" aria-controls="avatarMenu" title="Account">
                                <span class="absolute -inset-1 rounded-full opacity-0 group-hover:opacity-100 transition duration-300 bg-gradient-to-br from-amber-400/20 to-yellow-500/10 blur"></span>
                                <span class="relative block w-11 h-11 rounded-full overflow-hidden ring-2 ring-amber-400/60 avatar-ring transition-transform duration-200 group-active:scale-95">
                                    <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES); ?>" alt="Avatar" class="w-full h-full object-cover" />
                                </span>
                            </button>
                            <div id="avatarMenu" role="menu" aria-labelledby="avatarBtn" class="hidden absolute right-0 mt-2 w-44 rounded-lg border border-white/10 bg-slate-900/95 shadow-xl shadow-black/30 py-2 z-50">
                                <a href="<?php echo $baseUrl; ?>dashboard.php" class="block px-3 py-2 text-sm text-slate-200 hover:bg-white/5" role="menuitem">Dashboard</a>
                                <a href="#" class="block px-3 py-2 text-sm text-slate-200 hover:bg-white/5" role="menuitem">Profile</a>
                                <form method="post" action="<?php echo $baseUrl; ?>logout.php">
                                    <button type="submit" class="w-full text-left px-3 py-2 text-sm text-rose-300 hover:bg-white/5" role="menuitem">Logout</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-sm normal-case bg-amber-500 hover:bg-amber-600 border-none text-slate-900 font-semibold">Login</a>
                        <a href="<?php echo $baseUrl; ?>register.php" class="btn btn-sm normal-case bg-slate-200 hover:bg-white text-slate-900 border-none font-semibold">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <?php if ($loggedIn): ?>
            <div id="mobileNavWrapper" class="md:hidden max-w-11/12 mx-auto px-6"></div>
        <?php endif; ?>
    </header>

    <?php if ($loggedIn): ?>
        <script>
            // Avatar dropdown
            (function(){
                const btn = document.getElementById('avatarBtn');
                const menu = document.getElementById('avatarMenu');
                if(btn && menu){
                    const toggle = (open)=>{
                        menu.classList.toggle('hidden', !open);
                        btn.setAttribute('aria-expanded', open? 'true':'false');
                    };
                    btn.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(menu.classList.contains('hidden')); });
                    document.addEventListener('click', (e)=>{ if(!menu.classList.contains('hidden')) toggle(false); });
                    menu.addEventListener('click', (e)=> e.stopPropagation());
                }
            })();
            const burger = document.getElementById('hamburger');
            const desktopLinks = document.getElementById('navLinks');
            const mobileWrapper = document.getElementById('mobileNavWrapper');
            let mobileLinks;

            function buildMobile() {
                if (window.innerWidth < 860 && !mobileLinks) {
                    mobileLinks = desktopLinks.cloneNode(true);
                    mobileLinks.id = 'navLinksMobile';
                    mobileLinks.classList.remove('hidden', 'md:flex');
                    mobileLinks.classList.add('flex', 'flex-col', 'items-center', 'gap-6', 'py-6');
                    mobileWrapper.appendChild(mobileLinks);
                    mobileLinks.style.display = 'none';
                } else if (window.innerWidth >= 860 && mobileLinks) {
                    mobileLinks.remove();
                    mobileLinks = null;
                }
            }
            buildMobile();
            window.addEventListener('resize', buildMobile);
            if (burger) {
                burger.addEventListener('click', () => {
                    if (!mobileLinks) return;
                    const open = mobileLinks.style.display !== 'none';
                    mobileLinks.style.display = open ? 'none' : 'flex';
                    burger.classList.toggle('active', !open);
                    burger.setAttribute('aria-expanded', !open ? 'true' : 'false');
                });
            }
        </script>
    <?php endif; ?>
<!-- Body remains open; closed in footer -->