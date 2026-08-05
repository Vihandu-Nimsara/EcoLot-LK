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
    <meta name="description" content="EcoLot LK connects households, Municipal Councils and Authorized Recyclers through organized E-Waste collection and transparent E-Lot management.">
    <title>EcoLot LK | Smarter Municipal E-Waste Collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $safeBase ?>/assets/css/landing.css">
</head>
<body>
    <div class="landing-shell">
        <header class="site-header" aria-label="Main navigation">
            <a class="brand" href="<?= $safeBase ?>/" aria-label="EcoLot LK home">
                <img src="<?= $safeBase ?>/assets/images/ecolot-logo.png" alt="EcoLot LK">
            </a>

            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu">
                <span></span><span></span><span></span>
            </button>

            <nav class="main-navigation" id="main-navigation">
                <a href="#home">Home</a>
                <a href="#how-it-works">How it works</a>
                <a href="#benefits">Who it helps</a>
                <a href="#impact">Impact</a>
                <a href="#recyclers">For recyclers</a>
            </nav>

            <div class="header-actions">
                <a class="button button-secondary button-small" href="<?= $safeBase ?>/login">Login</a>
                <a class="button button-primary button-small" href="<?= $safeBase ?>/register">Register</a>
            </div>
        </header>

        <main>
            <section class="hero screen" id="home">
                <div class="hero-copy">
                    <p class="eyebrow">Organized municipal collection</p>
                    <h1>Give old electronics a <span>responsible next step.</span></h1>
                    <p class="hero-description">EcoLot LK connects households, Municipal Councils and Authorized Recyclers through area-based collections and transparent digital E-Lots.</p>

                    <div class="hero-actions">
                        <a class="button button-primary" href="<?= $safeBase ?>/register/public">Schedule a Collection</a>
                        <a class="button button-secondary" href="#how-it-works">See How It Works</a>
                    </div>

                    <p class="hero-note"><span aria-hidden="true">●</span> Your postal code identifies your Collection Area and available dates.</p>
                </div>

                <div class="hero-visual" aria-label="E-Waste moving from a household collection to an authorized recycler">
                    <div class="visual-card">
                        <div class="visual-topline">
                            <span>Collection journey</span>
                            <span class="live-badge"><i></i> Traceable</span>
                        </div>

                        <svg class="route-illustration" viewBox="0 0 680 390" role="img" aria-labelledby="route-title route-description">
                            <title id="route-title">EcoLot LK collection journey</title>
                            <desc id="route-description">A household, collection truck, municipal E-Lot and authorized recycler connected by a green route.</desc>
                            <defs>
                                <linearGradient id="route-fill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#e8f8ee"/>
                                    <stop offset="1" stop-color="#c8efd7"/>
                                </linearGradient>
                                <filter id="soft-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="12" stdDeviation="12" flood-color="#075b35" flood-opacity=".12"/>
                                </filter>
                            </defs>
                            <path d="M82 278 C170 176 245 315 342 205 S517 111 600 174" fill="none" stroke="#b7dfc5" stroke-width="9" stroke-linecap="round" stroke-dasharray="2 20"/>
                            <circle cx="83" cy="278" r="60" fill="white" filter="url(#soft-shadow)"/>
                            <path d="M48 280v-45l35-27 35 27v45H48Z" fill="url(#route-fill)" stroke="#0b7140" stroke-width="5" stroke-linejoin="round"/>
                            <rect x="72" y="248" width="22" height="32" rx="3" fill="#0b7140"/>
                            <rect x="55" y="242" width="13" height="13" rx="2" fill="#77d99b"/>
                            <rect x="99" y="242" width="13" height="13" rx="2" fill="#77d99b"/>

                            <g transform="translate(214 216)" filter="url(#soft-shadow)">
                                <rect x="0" y="12" width="136" height="72" rx="12" fill="#118847"/>
                                <path d="M136 35h38l25 25v24h-63V35Z" fill="#19a854"/>
                                <path d="M151 44h18l15 16h-33V44Z" fill="#dff7e8"/>
                                <circle cx="40" cy="86" r="17" fill="#173c2a" stroke="white" stroke-width="7"/>
                                <circle cx="160" cy="86" r="17" fill="#173c2a" stroke="white" stroke-width="7"/>
                                <path d="M59 29h54M86 20v40" stroke="#8ce4aa" stroke-width="5" stroke-linecap="round"/>
                                <path d="M83 32l-12 20h25L83 32Zm13 20 8-14m-34 14-7-13" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>

                            <g transform="translate(397 118)" filter="url(#soft-shadow)">
                                <rect width="104" height="104" rx="24" fill="white"/>
                                <rect x="25" y="26" width="54" height="51" rx="8" fill="#dff7e8" stroke="#118847" stroke-width="4"/>
                                <path d="M34 40h36M34 51h25M34 62h31" stroke="#118847" stroke-width="4" stroke-linecap="round"/>
                                <circle cx="76" cy="76" r="18" fill="#24c767"/>
                                <path d="m68 76 6 6 11-13" fill="none" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>

                            <g transform="translate(545 106)" filter="url(#soft-shadow)">
                                <circle cx="56" cy="56" r="56" fill="#075b35"/>
                                <path d="M35 66c2-18 10-28 21-28s19 10 21 28" fill="none" stroke="#8ce4aa" stroke-width="8" stroke-linecap="round"/>
                                <circle cx="56" cy="31" r="12" fill="#8ce4aa"/>
                                <path d="M37 76h38" stroke="white" stroke-width="7" stroke-linecap="round"/>
                                <path d="M85 31c10 4 15 12 15 24" fill="none" stroke="white" stroke-width="5" stroke-linecap="round"/>
                            </g>

                            <text x="84" y="363" text-anchor="middle">Household</text>
                            <text x="313" y="347" text-anchor="middle">Council pickup</text>
                            <text x="449" y="96" text-anchor="middle">Digital E-Lot</text>
                            <text x="601" y="196" text-anchor="middle">Recycler</text>
                        </svg>

                        <div class="visual-status">
                            <span><b>01</b> Request</span>
                            <span><b>02</b> Collect</span>
                            <span><b>03</b> Process</span>
                        </div>
                    </div>
                </div>

                <div class="trust-strip" aria-label="EcoLot LK service highlights">
                    <article><strong>Area-based dates</strong><span>Collection options for your location</span></article>
                    <article><strong>Digital E-Lots</strong><span>Organized by Municipal Councils</span></article>
                    <article><strong>Authorized processing</strong><span>Eligible recyclers bid and complete work</span></article>
                </div>
            </section>

            <section class="journey screen" id="how-it-works">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">How EcoLot LK works</p>
                        <h2>One clear journey from request to recovery.</h2>
                    </div>
                    <p>Every stage is recorded digitally, giving each participant the right information at the right time.</p>
                </div>

                <ol class="workflow" aria-label="E-Waste collection process">
                    <li><span>1</span><div><h3>Register your area</h3><p>Your postal code identifies the correct Collection Area.</p></div></li>
                    <li><span>2</span><div><h3>Choose a date</h3><p>Select an available monthly date and submit your request.</p></div></li>
                    <li><span>3</span><div><h3>Council creates E-Lots</h3><p>Collected items are grouped into organized digital lots.</p></div></li>
                    <li><span>4</span><div><h3>Recyclers bid</h3><p>Eligible Authorized Recyclers submit transparent Bids.</p></div></li>
                    <li><span>5</span><div><h3>Award and process</h3><p>The chosen recycler completes Processing and updates status.</p></div></li>
                </ol>

                <div class="benefits" id="benefits">
                    <div class="benefits-intro">
                        <p class="eyebrow">One platform, shared value</p>
                        <h2>Built for everyone involved.</h2>
                        <p>Choose a role to see how EcoLot LK makes the municipal E-Waste workflow simpler.</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-tabs" role="tablist" aria-label="Benefits by participant">
                            <button class="benefit-tab is-active" type="button" role="tab" id="citizens-tab" aria-selected="true" aria-controls="citizens-panel" tabindex="0" data-benefit-tab="citizens">Citizens</button>
                            <button class="benefit-tab" type="button" role="tab" id="councils-tab" aria-selected="false" aria-controls="councils-panel" tabindex="-1" data-benefit-tab="councils">Councils</button>
                            <button class="benefit-tab" type="button" role="tab" id="recyclers-tab" aria-selected="false" aria-controls="recyclers-panel" tabindex="-1" data-benefit-tab="recyclers">Recyclers</button>
                        </div>

                        <div class="benefit-panel" role="tabpanel" id="citizens-panel" aria-labelledby="citizens-tab" data-benefit-panel="citizens">
                            <div><p class="panel-kicker">Convenient local access</p><h3>Plan collection around your area.</h3></div>
                            <ul><li>Area-specific collection dates</li><li>Simple online requests</li><li>Clear collection status</li></ul>
                        </div>
                        <div class="benefit-panel" role="tabpanel" id="councils-panel" aria-labelledby="councils-tab" data-benefit-panel="councils" hidden>
                            <div><p class="panel-kicker">Organized municipal management</p><h3>Coordinate collections and E-Lots clearly.</h3></div>
                            <ul><li>Structured collection records</li><li>Digital E-Lot management</li><li>Transparent recycler awards</li></ul>
                        </div>
                        <div class="benefit-panel" role="tabpanel" id="recyclers-panel" aria-labelledby="recyclers-tab" data-benefit-panel="recyclers" hidden>
                            <div><p class="panel-kicker">Eligible processing opportunities</p><h3>Put approved capacity to work.</h3></div>
                            <ul><li>Relevant municipal E-Lots</li><li>Transparent bidding</li><li>Clear award and Processing flow</li></ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="impact screen" id="impact">
                <div class="impact-overview">
                    <div class="impact-copy">
                        <p class="eyebrow">A cleaner, traceable network</p>
                        <h2>Responsible disposal becomes easier when every handoff is visible.</h2>
                        <p>EcoLot LK replaces scattered collection information with one coordinated process for communities, councils and recyclers.</p>
                    </div>

                    <div class="impact-grid">
                        <article><span>01</span><strong>Convenient</strong><p>Local dates make responsible disposal easier for households.</p></article>
                        <article><span>02</span><strong>Organized</strong><p>Digital records help councils coordinate every collection.</p></article>
                        <article><span>03</span><strong>Transparent</strong><p>Eligible recyclers receive fair access to relevant E-Lots.</p></article>
                        <article><span>04</span><strong>Traceable</strong><p>Status updates follow items through completed Processing.</p></article>
                    </div>
                </div>

                <div class="recycler-banner" id="recyclers">
                    <div class="recycler-icon" aria-hidden="true">↻</div>
                    <div><p class="eyebrow">For Authorized Recyclers</p><h3>Turn approved capacity into community impact.</h3></div>
                    <p>Discover eligible E-Lots, submit Bids and manage awarded Processing in one place.</p>
                    <a class="text-link" href="<?= $safeBase ?>/register/recycler">Join as a Recycler <span>→</span></a>
                </div>

                <div class="closing-card">
                    <div>
                        <p class="eyebrow">Your next collection starts here</p>
                        <h2>Ready to clear out E-Waste responsibly?</h2>
                        <p>Register, find your area's next date and submit a Collection Request.</p>
                    </div>
                    <div class="closing-actions">
                        <a class="button button-light" href="<?= $safeBase ?>/register/public">Register Now</a>
                        <a class="button button-dark-outline" href="<?= $safeBase ?>/login">Login</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <img src="<?= $safeBase ?>/assets/images/ecolot-logo.png" alt="EcoLot LK">
            <p>Responsible municipal E-Waste collection.</p>
            <nav aria-label="Footer navigation">
                <a href="#how-it-works">How it works</a>
                <a href="#impact">Our impact</a>
                <a href="#recyclers">For recyclers</a>
                <a href="<?= $safeBase ?>/login">Login</a>
            </nav>
            <span>&copy; <?= date('Y') ?> EcoLot LK</span>
        </footer>
    </div>

    <script src="<?= $safeBase ?>/assets/js/landing.js" defer></script>
</body>
</html>
