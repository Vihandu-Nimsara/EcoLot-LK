<?php
declare(strict_types=1);

$assetBase = $basePath ?? '/EcoLot-LK/public';
$safeBase = htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EcoLot LK connects households, Municipal Councils, collectors, and authorized recyclers through scheduled e-waste collections and transparent digital E-Lots.">
    <title>EcoLot LK | Municipal E-Waste Collection & E-Lot Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $safeBase ?>/assets/css/landing.css">
</head>
<body>
    <header class="site-header" aria-label="Main navigation">
        <a class="brand" href="<?= $safeBase ?>/" aria-label="EcoLot LK home">
            <img src="<?= $safeBase ?>/assets/images/ecolot-logo.png" alt="EcoLot LK" width="1920" height="672">
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-navigation" id="main-navigation">
            <a href="#how-it-works">How it works</a>
            <a href="#who-it-helps">Who it connects</a>
            <a href="#recyclers">For recyclers</a>
            <a class="mobile-login" href="<?= $safeBase ?>/login">Login</a>
        </nav>

        <div class="header-actions">
            <a class="text-button" href="<?= $safeBase ?>/login">Login</a>
            <a class="button button-primary button-small" href="<?= $safeBase ?>/register/public">Create a request</a>
        </div>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="hero-copy">
                <p class="eyebrow"><span></span> One connected municipal system</p>
                <h1>Local collection.<br><em>Responsible processing.</em></h1>
                <p class="hero-description">EcoLot LK connects households, Municipal Councils, collectors, and authorized recyclers through one traceable e-waste journey.</p>

                <div class="hero-actions">
                    <a class="button button-primary" href="<?= $safeBase ?>/register/public">
                        Create a pickup request
                        <span aria-hidden="true">→</span>
                    </a>
                    <a class="button button-secondary" href="#how-it-works">See how it works</a>
                </div>

                <ul class="trust-list" aria-label="Service highlights">
                    <li><span aria-hidden="true">✓</span> Area-based schedules</li>
                    <li><span aria-hidden="true">✓</span> Council-coordinated pickups</li>
                    <li><span aria-hidden="true">✓</span> Authorized processing</li>
                </ul>
            </div>

            <div class="hero-media">
                <div class="image-placeholder image-placeholder-hero">
                    <img
                        src="<?= $safeBase ?>/assets/images/landing/municipal-collection.jpeg"
                        alt="Household e-waste handover during a municipal collection"
                        class="landing-photo"
                        width="1254"
                        height="1254"
                        fetchpriority="high"
                    >
                </div>

                <div class="pickup-card">
                    <span class="pickup-status"><i></i> Request scheduled</span>
                    <strong>Area collection date</strong>
                    <div><b>24</b><span>August<br>Saturday</span></div>
                </div>

                <div class="route-card" aria-label="Collection journey">
                    <span>Request</span><i></i><span>E-Lot</span><i></i><span>Processing</span>
                </div>
            </div>
        </section>

        <section class="process section" id="how-it-works">
            <div class="section-heading centered-heading">
                <p class="eyebrow">How EcoLot LK works</p>
                <h2>From pickup request to verified processing.</h2>
                <p>Each role moves the same recorded workflow forward.</p>
            </div>

            <ol class="process-grid">
                <li>
                    <div class="step-number">01</div>
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M6 3v3M18 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="m9 15 2 2 4-5"/></svg>
                    </div>
                    <h3>Request by area</h3>
                    <p>Use your postal code, choose an available date, and describe the items for collection.</p>
                </li>
                <li>
                    <div class="step-number">02</div>
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    </div>
                    <h3>Collect and verify</h3>
                    <p>The council assigns the route; the collector records the pickup, weight, and condition.</p>
                </li>
                <li>
                    <div class="step-number">03</div>
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3v4l3-3M12 7a6 6 0 0 1 5.6 3.8M20 12h-4l3 3M16 17.2A6 6 0 0 1 9 18M8 21v-4l-3 3M5 17a6 6 0 0 1-.6-6.2M4 8h4L5 5"/></svg>
                    </div>
                    <h3>Award and process</h3>
                    <p>Verified items form E-Lots. Eligible recyclers bid, and the winner updates processing status.</p>
                </li>
            </ol>
        </section>

        <section class="audience section" id="who-it-helps">
            <div class="audience-layout">
                <div class="audience-copy">
                    <p class="eyebrow">One coordinated network</p>
                    <h2>One coordinated network.<br>One shared record.</h2>
                    <p>Purpose-built workspaces keep households, councils, collectors, and recyclers connected.</p>
                </div>

                <div class="role-grid">
                    <article class="role-card role-card-featured">
                        <img
                            src="<?= $safeBase ?>/assets/images/landing/household-request.png"
                            alt="Household e-waste request and item drop-off"
                            class="audience-card__image"
                            width="1254"
                            height="1254"
                            loading="lazy"
                            decoding="async"
                            >
                        <h3>Request with confidence</h3>
                        <p>See dates for your postal-code area, add item details, and follow every request.</p>
                        <a href="<?= $safeBase ?>/register/public">Create a request <span>→</span></a>
                    </article>
                    <article class="role-card role-card-council">
                        <span class="role-label">For councils</span>
                        <h3>Manage the full cycle</h3>
                        <p>Plan area schedules, coordinate routes, verify pickups, create E-Lots, and review bids.</p>
                    </article>
                    <article class="role-card role-card-recycler" id="recyclers">
                        <span class="role-label">For recyclers</span>
                        <h3>Bid on eligible E-Lots</h3>
                        <p>Find lots that match approved capabilities, submit bids, and report processing progress.</p>
                        <a href="<?= $safeBase ?>/register/recycler">Join as a recycler <span>→</span></a>
                    </article>
                </div>
            </div>
        </section>

        <section class="story section">
            <div class="story-card">
                <div class="image-placeholder image-placeholder-story">
                    <img
                        src="<?= $safeBase ?>/assets/images/landing/elot-handover.png"
                        alt="E-waste E-Lot handover for authorized recycling"
                        class="landing-photo"
                        width="1066"
                        height="941"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="story-copy">
                    <p class="eyebrow light-eyebrow">Traceability across every handoff</p>
                    <h2>One record follows the journey—not just the pickup.</h2>
                    <p>Schedules, collection records, E-Lot bids, awards, and processing updates stay connected in EcoLot LK.</p>
                    <div class="story-points">
                        <span>Scheduled by area</span>
                        <span>Verified by councils</span>
                        <span>Awarded transparently</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="final-cta section">
            <div>
                <p class="eyebrow">Start with your collection area</p>
                <h2>Find your next available e-waste collection date.</h2>
            </div>
            <div class="final-actions">
                <a class="button button-primary" href="<?= $safeBase ?>/register/public">Create a pickup request <span aria-hidden="true">→</span></a>
                <span>Already registered? <a href="<?= $safeBase ?>/login">Login</a></span>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-brand">
            <img src="<?= $safeBase ?>/assets/images/ecolot-logo.png" alt="EcoLot LK" width="1920" height="672">
            <p>Municipal e-waste collection and E-Lot management for Sri Lanka.</p>
        </div>
        <nav aria-label="Footer navigation">
            <a href="#how-it-works">How it works</a>
            <a href="#who-it-helps">Who it connects</a>
            <a href="#recyclers">For recyclers</a>
            <a href="<?= $safeBase ?>/login">Login</a>
        </nav>
        <span>&copy; <?= date('Y') ?> EcoLot LK</span>
    </footer>

    <script src="<?= $safeBase ?>/assets/js/landing.js" defer></script>
</body>
</html>
