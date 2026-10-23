<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ePalmHaul — FFB Hauling Operations · KPII</title>
<meta name="description" content="Fresh fruit bunch hauling, weighbridge and settlement workflow for Kenram Palm Oil Industry Incorporation.">
<style>
    :root {
        --ink: #18221d;
        --paper: #f4f2ec;
        --pine: #0f3b2c;
        --pine-2: #155e46;
        --amber: #b6801f;
        --clay: #8a4a2b;
        --steel: #3c5563;
        --line: #dcd8cc;
        --line-2: #c7c2b4;
        --muted: #5d665f;
    }

    * { box-sizing: border-box; }

    html { -webkit-text-size-adjust: 100%; }

    body {
        margin: 0;
        background: var(--paper);
        color: var(--ink);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 16px;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
    }

    .wrap { max-width: 1120px; margin: 0 auto; padding: 0 22px; }

    a { color: inherit; }

    /* Utility bar */
    .utilbar {
        background: var(--pine);
        color: #cfe0d5;
        font-size: 12.5px;
        letter-spacing: .02em;
    }
    .utilbar .wrap {
        display: flex; justify-content: space-between; align-items: center;
        gap: 16px; padding-top: 8px; padding-bottom: 8px; flex-wrap: wrap;
    }
    .utilbar .live { display: inline-flex; align-items: center; gap: 7px; }
    .dot { width: 8px; height: 8px; border-radius: 50%; background: #55c38a; display: inline-block; }

    /* Header */
    header.site {
        background: var(--paper);
        border-bottom: 1px solid var(--line);
    }
    header.site .wrap {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 18px; padding-bottom: 18px; gap: 18px;
    }
    .brand { display: flex; align-items: center; gap: 13px; text-decoration: none; }
    .mark {
        width: 40px; height: 40px; flex: none;
        background: var(--pine); color: #fff;
        display: grid; place-items: center;
        font-weight: 700; font-size: 15px; letter-spacing: -.02em;
        border-radius: 5px;
    }
    .brand .name { font-weight: 700; font-size: 18px; letter-spacing: -.01em; line-height: 1.1; }
    .brand .sub { font-size: 11.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .12em; }

    .topnav { display: flex; align-items: center; gap: 26px; font-size: 14px; }
    .topnav a { text-decoration: none; color: var(--muted); }
    .topnav a:hover { color: var(--pine); }
    .topnav .cta {
        color: #fff; background: var(--pine);
        padding: 9px 15px; border-radius: 5px; font-weight: 600;
    }
    .topnav .cta:hover { background: var(--pine-2); color: #fff; }

    /* Hero */
    .hero { padding: 68px 0 60px; }
    .hero .wrap {
        display: grid; grid-template-columns: 1.25fr .75fr; gap: 56px; align-items: start;
    }

    .overline {
        font-size: 12px; text-transform: uppercase; letter-spacing: .2em;
        color: var(--amber); font-weight: 700; margin-bottom: 20px;
    }
    h1.display {
        font-family: Georgia, "Times New Roman", serif;
        font-weight: 600;
        font-size: clamp(2.1rem, 4.6vw, 3.3rem);
        line-height: 1.08;
        letter-spacing: -.015em;
        margin: 0 0 22px;
    }
    h1.display em { font-style: normal; color: var(--pine-2); }
    .lead { font-size: 17.5px; color: var(--muted); max-width: 50ch; margin: 0 0 30px; }

    .facts { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; }
    .facts li {
        padding: 4px 26px 4px 0; margin-right: 26px;
        border-right: 1px solid var(--line-2);
    }
    .facts li:last-child { border-right: 0; margin-right: 0; }
    .facts .k { display: block; font-size: 11.5px; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); }
    .facts .v { display: block; font-weight: 600; font-size: 15px; color: var(--ink); margin-top: 3px; }

    /* Portal access panel */
    .panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 7px;
        box-shadow: 0 12px 30px -22px rgba(15, 59, 44, .35);
        overflow: hidden;
    }
    .panel-head {
        display: flex; justify-content: space-between; align-items: baseline;
        padding: 16px 20px; border-bottom: 1px solid var(--line);
        background: #fbfaf6;
    }
    .panel-head h2 { margin: 0; font-size: 14px; text-transform: uppercase; letter-spacing: .12em; }
    .panel-head span { font-size: 12.5px; color: var(--muted); }

    .portal {
        display: grid; grid-template-columns: 42px 1fr 20px; align-items: center; gap: 15px;
        padding: 16px 20px; text-decoration: none;
        border-bottom: 1px solid var(--line);
        transition: background .15s ease;
    }
    .portal:last-child { border-bottom: 0; }
    .portal:hover { background: #f3f5ef; }
    .mono {
        width: 42px; height: 42px; border-radius: 5px;
        display: grid; place-items: center;
        color: #fff; font-weight: 700; font-size: 14px; letter-spacing: -.02em;
    }
    .mono.admin { background: var(--pine); }
    .mono.farmer { background: var(--amber); }
    .mono.driver { background: var(--steel); }
    .mono.wb { background: var(--clay); }
    .portal .t { font-weight: 600; font-size: 15.5px; }
    .portal .d { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .portal .arrow { color: var(--muted); justify-self: end; }
    .portal:hover .arrow { color: var(--pine); }
    .panel-foot {
        padding: 13px 20px; font-size: 13.5px; color: var(--muted);
        background: #fbfaf6; border-top: 1px solid var(--line);
    }
    .panel-foot a { color: var(--pine-2); font-weight: 600; text-decoration: none; }
    .panel-foot a:hover { text-decoration: underline; }

    /* Footer */
    footer.site {
        border-top: 1px solid var(--line);
        padding: 22px 0;
        color: var(--muted); font-size: 13px;
    }
    footer.site .wrap { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    footer.site strong { color: var(--ink); font-weight: 700; }

    @media (max-width: 880px) {
        .hero .wrap { grid-template-columns: 1fr; gap: 40px; }
        .hero { padding: 44px 0 46px; }
        .topnav .link-hide { display: none; }
    }
</style>
</head>
<body>

<div class="utilbar">
    <div class="wrap">
        <span>Kenram Palm Oil Industry Incorporation &middot; Fresh Fruit Bunch Hauling Operations</span>
        <span class="live"><span class="dot"></span> System operational</span>
    </div>
</div>

<header class="site">
    <div class="wrap">
        <a class="brand" href="index.php">
            <span class="mark">ePH</span>
            <span>
                <span class="name">ePalmHaul</span><br>
                <span class="sub">KPII Logistics</span>
            </span>
        </a>
        <nav class="topnav">
            <a class="link-hide" href="#portals">Portals</a>
            <a class="link-hide" href="farmer/register.php">Register</a>
            <a class="cta" href="admin/login.php">Staff Login</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero" id="portals">
        <div class="wrap">
            <div>
                <div class="overline">Harvest &middot; Haul &middot; Weigh &middot; Settle</div>
                <h1 class="display">
                    Every fresh fruit bunch, tracked from the <em>estate row to the weighbridge.</em>
                </h1>
                <p class="lead">
                    ePalmHaul runs the daily hauling workflow &mdash; harvest requests, driver and
                    truck assignment, weighbridge tickets and FFB settlement &mdash; in a single
                    operational record. Choose your portal to sign in.
                </p>
                <ul class="facts">
                    <li><span class="k">Weighing</span><span class="v">Ticket per delivery</span></li>
                    <li><span class="k">Settlement</span><span class="v">FFB, incentive &amp; haulage</span></li>
                    <li><span class="k">Coverage</span><span class="v">Estate, mill &amp; weighbridge</span></li>
                </ul>
            </div>

            <aside class="panel">
                <div class="panel-head">
                    <h2>Portal access</h2>
                    <span>4 roles</span>
                </div>

                <a class="portal" href="admin/login.php">
                    <span class="mono admin">AD</span>
                    <span>
                        <span class="t">Administrator</span>
                        <span class="d">Approve requests, assign crews, pricing and reports.</span>
                    </span>
                    <span class="arrow">&rarr;</span>
                </a>

                <a class="portal" href="farmer/login.php">
                    <span class="mono farmer">FR</span>
                    <span>
                        <span class="t">Farmer</span>
                        <span class="d">Raise harvest requests and follow your hauling schedule.</span>
                    </span>
                    <span class="arrow">&rarr;</span>
                </a>

                <a class="portal" href="driver/login.php">
                    <span class="mono driver">DR</span>
                    <span>
                        <span class="t">Driver</span>
                        <span class="d">Open assigned trips and update delivery progress.</span>
                    </span>
                    <span class="arrow">&rarr;</span>
                </a>

                <a class="portal" href="weighbridge/login.php">
                    <span class="mono wb">WB</span>
                    <span>
                        <span class="t">Weighbridge Operator</span>
                        <span class="d">Log gross and tare weights and issue scale tickets.</span>
                    </span>
                    <span class="arrow">&rarr;</span>
                </a>

                <div class="panel-foot">
                    Farmer without an account? <a href="farmer/register.php">Register here</a>.
                </div>
            </aside>
        </div>
    </section>
</main>

<footer class="site">
    <div class="wrap">
        <span><strong>ePalmHaul</strong> &mdash; FFB Hauling Management System</span>
        <span>&copy; 2026 Kenram Palm Oil Industry Incorporation. Authorised personnel only.</span>
    </div>
</footer>

</body>
</html>
