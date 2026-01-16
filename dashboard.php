<?php
// dashboard.php
// Frontend for Enrolment Insights 2.0
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Enrolment Insights</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --aih-green-primary: #256B37;
            --aih-green-dark: #034f27;
            --aih-green-light: #B0D8A1;
            --aih-text-dark: #404040;
            --aih-bg-light: #f9fafb;
            --card-border: rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--aih-bg-light);
            color: var(--aih-text-dark);
            -webkit-font-smoothing: antialiased;
        }

        .navbar {
            background: #fff;
            border-bottom: 2px solid var(--aih-green-primary);
            box-shadow: 0 4px 12px rgba(37, 107, 55, 0.05);
        }

        .navbar-brand {
            color: var(--aih-text-dark) !important;
            font-weight: 700;
            letter-spacing: -0.5px;
            font-size: 1.25rem;
        }

        .btn-primary {
            background-color: var(--aih-green-primary);
            border-color: var(--aih-green-primary);
            border-radius: 2px;
            /* Sharp corners per brand */
            font-weight: 600;
            padding: 8px 20px;
        }

        .btn-primary:hover {
            background-color: var(--aih-green-dark);
            border-color: var(--aih-green-dark);
        }

        .card {
            border: 1px solid var(--card-border);
            border-radius: 4px;
            /* Slightly sharper */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            border-color: var(--aih-green-light);
        }

        .unit-code {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--aih-green-primary);
        }

        .unit-total {
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            background: var(--aih-green-primary);
            padding: 4px 12px;
            border-radius: 2px;
        }

        .nav-pills .nav-link.active {
            background-color: var(--aih-green-primary);
            border-radius: 2px;
        }

        .nav-pills .nav-link {
            color: var(--aih-text-dark);
            font-weight: 600;
        }

        .block-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .block-row:last-child {
            border-bottom: none;
        }

        .block-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--aih-text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .lecturer-name {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
            display: inline-block;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .campus-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 2px;
            text-transform: uppercase;
        }

        .badge-mel {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-syd {
            background: #fce7f3;
            color: #be185d;
            border: 1px solid #fbcfe8;
        }

        .badge-comb {
            background: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .search-input {
            border-radius: 2px;
            border: 1px solid #d1d5db;
            padding: 10px 16px;
        }

        .search-input:focus {
            border-color: var(--aih-green-primary);
            box-shadow: 0 0 0 2px var(--aih-green-light);
        }

        /* Loader */
        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.98);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.3s ease;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--aih-green-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--aih-green-primary);
            margin-bottom: 5px;
        }

        .loading-subtext {
            font-size: 0.95rem;
            color: #666;
            font-style: italic;
            min-height: 1.5em;
            /* Prevent layout shift */
        }

        /* Bootstrap Overrides for Theme */
        .bg-primary {
            background-color: var(--aih-green-primary) !important;
        }

        .text-primary {
            color: var(--aih-green-primary) !important;
        }
    </style>
</head>

<body>

    <!-- Loader -->
    <div id="loader" class="loader-overlay">
        <div class="spinner"></div>
        <div class="loading-text">Loading Data...</div>
        <div class="loading-subtext" id="loadingMsg">Waking up the server...</div>
        <div class="fw-semibold text-success mt-2 small" id="pendingCount"></div>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="https://as.aih.edu.au/logo-green.svg" alt="AIHE Logo" style="height: 50px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                </svg>
                Enrolment Insights
            </a>

            <div class="ms-auto d-flex gap-3">
                <input type="text" id="searchInput" class="form-control search-input"
                    placeholder="Search units or blocks..." style="width: 280px;">
                 <button class="btn btn-outline-success rounded-pill px-3 fw-medium text-nowrap" id="btnLoadLabels" onclick="fetchSessionLabels()">
                    <i class="bi bi-tags-fill me-1"></i> Load Names
                 </button>
                <button id="btnRefresh" class="btn btn-dark rounded-pill px-4 fw-medium">Refresh</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">

        <!-- Header with Branding -->
        <div class="row mb-5 align-items-center">
            <div class="col-8"></div>
        </div>

        <!-- Stats KPIs -->
        <div class="row mb-5 g-4">
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wide"
                            title="Distinct students with Enrolled status">Total Students</div>
                        <div class="display-6 fw-bold text-primary mt-1" id="kpiUnique">—</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wide"
                            title="Units with at least one enrolment">Open Active Units</div>
                        <div class="display-6 fw-bold text-dark mt-1" id="kpiUnits">—</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wide"
                            title="Total number of active class blocks being taught">Total Groups</div>
                        <div class="display-6 fw-bold text-dark mt-1" id="kpiGroups">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content (Left) -->
            <div class="col-lg-9">

                <!-- Navigation Tabs -->
                <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="pills-dash-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-dash" type="button">Dashboard</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="pills-sugg-tab" data-bs-toggle="pill" data-bs-target="#pills-sugg"
                            type="button">⚡ Group Suggestions (Early Finishers)</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="pills-risk-tab" data-bs-toggle="pill" data-bs-target="#pills-risk"
                            type="button">⚠ At Risk Students</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="pills-reten-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-reten" type="button">📊 Retention Analysis</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">

                    <!-- Tab 1: Dashboard (Unit Cards) -->
                    <div class="tab-pane fade show active" id="pills-dash">
                        <div id="cardsGrid" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                            <!-- Cards injected via JS -->
                        </div>
                    </div>

                    <!-- Tab 2: Suggestions (Early Finishers) -->
                    <div class="tab-pane fade" id="pills-sugg">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold">
                                ⚠ Students with Visa Expiry before Course End Date ("Rush Cohort")
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-4">
                                    These students must complete their course early due to visa constraints.
                                    The table below suggests potential <strong>Group Creations</strong> based on student
                                    volume per Course and Campus.
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle" id="suggestionTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Course / Program</th>
                                                <th>Campus</th>
                                                <th>Affected Students</th>
                                                <th>Suggested Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Injected via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2.5: At Risk Students (Encumbrance/SAR) -->
                    <div class="tab-pane fade" id="pills-risk">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-danger-subtle text-danger-emphasis fw-bold">
                                ⚠ Encumbered & At-Risk Students
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-4">
                                    Students flagged with <strong>Academic Risks (SAR, Probation)</strong> or
                                    <strong>Encumbrances (Financial, Library)</strong>.
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle" id="atRiskTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Student</th>
                                                <th>Course / Campus</th>
                                                <th>Risk Category</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Injected via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Retention -->
                    <div class="tab-pane fade" id="pills-reten">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-secondary mb-2">Block-to-Block Retention Flow</h5>
                                <p class="small text-muted mb-4">
                                    Shows the percentage of unique students from one block who are also enrolled in the
                                    subsequent block.
                                    <br><em>(e.g., How many students from Block 1 continued into Block 2)</em>
                                </p>
                                <div id="retentionFlow"
                                    class="d-flex flex-wrap gap-4 align-items-center justify-content-center py-5">
                                    <!-- Injected via JS -->
                                    <div class="text-muted fst-italic">Loading retention data...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Sidebar (Right) -->
            <div class="col-lg-3">
                <div class="sticky-top" style="top: 90px; z-index: 100;">

                    <!-- Risks / Low Enrolment Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-secondary fw-bold">Unit Risk / Low Enrolment</h6>
                            <span class="badge bg-danger text-white" id="riskCount">0</span>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
                                    <table class="table table-sm table-hover mb-0" id="riskTable"
                                        style="font-size: 0.8rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Unit</th>
                                                <th>Block</th>
                                                <th>#</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive Units Table -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-secondary fw-bold">Units with no enrolment</h6>
                            <span class="badge bg-light text-secondary border" id="closedCount">0</span>
                        </div>
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
                                    <table class="table table-sm table-hover mb-0" id="closedTable"
                                        style="font-size: 0.85rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Unit</th>
                                                <th>Block</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-muted small"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global Error Handler
        window.onerror = function (msg, url, line) {
            const loader = document.getElementById("loader");
            if (loader) loader.style.display = "none";
            alert("JS Error: " + msg + "\nLine: " + line);
            return false;
        };
    </script>
    <script>
        const API_URL = "scheduled_unit_counts.php";
        let globalData = null;

        // Funny messages (Team Theme)
        const messages = [
            "Waking up Rob and Annie's Paradigm (Coffee required)...",
            "Investigating Mehedi and Sajan's \"Organised Chaos\"...",
            "Checking in with our SX First Responders...",
            "Giving Rian's SAR a gentle, reassuring pat...",
            "Translating Bryn's Vision into Metrics...",
            "Attempting to Locate Gordon's Retention KPI... Just hanging in there."
        ];

        let msgIdx = 0;
        const msgInterval = setInterval(() => {
            const el = document.getElementById("loadingMsg");
            if (el) {
                el.innerText = messages[msgIdx % messages.length];
                msgIdx++;
            }
        }, 1500);

        async function loadData() {
            const loader = document.getElementById("loader");
            if (loader) loader.style.display = "flex";

            // Artificial delay start time
            const startTime = Date.now();
            const MIN_LOADING_TIME = 3000; // 3 seconds minimum to show funny messages

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 300000);

            try {
                const res = await fetch(`${API_URL}?t=${Date.now()}`, { signal: controller.signal });
                clearTimeout(timeoutId);
                if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}`);
                const json = await res.json();

                // Calculate remaining time to wait
                const elapsed = Date.now() - startTime;
                const remaining = Math.max(0, MIN_LOADING_TIME - elapsed);

                // Wait the remaining time if needed
                if (remaining > 0) {
                    await new Promise(r => setTimeout(r, remaining));
                }

                globalData = json;
                render(json);
                renderSuggestions(json);
                renderAtRisk(json);
                renderRetention(json);

                if (json.groups) {
                    const pendingCount = json.groups.filter(g => g.source === 'pending').length;
                    if (pendingCount > 0) {
                        const countEl = document.getElementById("pendingCount");
                        if (countEl) countEl.innerText = `${pendingCount} units still loading...`;
                        setTimeout(loadData, 1000);
                        return;
                    }
                }
            } catch (e) {
                console.error(e);
                alert("Error: " + e.message);
            } finally {
                const stillLoading = (globalData && globalData.groups && globalData.groups.some(g => g.source === 'pending'));
                if (!stillLoading && loader) {
                    clearInterval(msgInterval);
                    loader.style.opacity = '0'; // Fade out
                    setTimeout(() => loader.style.display = "none", 300);
                }
            }
        }

        function getGroupLabel(blockName) {
            const m = blockName.match(/Block\s*(\d+)/i);
            if (m) return `Group ${m[1]}`;
            return "Group 1";
        }

        function render(data) {
            if (!data) return;
            // Prefer detailed_groups if available, else fallback (though we expect backend to send it)
            const unitGroups = data.detailed_groups || {};
            const expectedGroups = data.groups || [];

            let unitCount = 0;
            let totalScheduledGroups = expectedGroups.length || 0;
            const cards = [];

            // Reset collections
            const activeUnitCodes = new Set();
            const riskItems = [];
            if (typeof window.riskItems !== 'undefined') window.riskItems = riskItems; // sync global if needed

            const search = document.getElementById("searchInput") ? document.getElementById("searchInput").value.trim().toLowerCase() : "";

            // If unitGroups is empty (PHP [] -> JS []), handle it
            const entries = Array.isArray(unitGroups) ? [] : Object.entries(unitGroups);

            // Iterate Units
            for (const [unitCode, blocks] of entries) {
                if (unitCode === "MATERIAL_FEE") continue;

                let unitTotal = 0;
                let totalMel = 0;
                let totalSyd = 0;
                let totalComb = 0;

                const blockList = [];

                // Iterate Blocks
                for (const [blockName, groups] of Object.entries(blocks)) {
                    // groups is an array of Group Objects
                    // Sort groups by Campus (MEL, SYD, COMB) then Lecturer
                    groups.sort((a, b) => {
                        if (a.campus !== b.campus) return a.campus.localeCompare(b.campus);
                        return (a.lecturer || "").localeCompare(b.lecturer || "");
                    });

                    let blockTotal = 0;

                    // Process groups for display
                    const displayGroups = groups.map(g => {
                        unitTotal += g.enrolled_count;
                        blockTotal += g.enrolled_count;

                        if (g.campus === 'MEL') totalMel += g.enrolled_count;
                        else if (g.campus === 'SYD') totalSyd += g.enrolled_count;
                        else if (g.campus === 'COMB') totalComb += g.enrolled_count;

                        // Risk Logic (Low Enrolment <= 10)
                        if (g.enrolled_count > 0 && g.enrolled_count <= 10) {
                            riskItems.push({
                                unitCode,
                                grpLabel: `Group ${g.id}`,
                                blockName: blockName,
                                campus: g.campus,
                                count: g.enrolled_count
                            });
                        }

                        return {
                            ...g, label: `Group (ID: ${g.id || 'N/A'})` // Can be refined if we have 'Group 1' etc from name
                        };
                    });

                    blockList.push({
                        name: blockName,
                        groups: displayGroups,
                        total: blockTotal
                    });
                }

                // Search Filter
                const hay = (unitCode + " " + blockList.map(b => b.name).join(" ")).toLowerCase();
                if (search && !hay.includes(search)) continue;

                unitCount++;
                activeUnitCodes.add(unitCode);

                // Sort blocks chronologically
                blockList.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));

                cards.push({ unitCode, unitTotal, blockList, breakdown: { mel: totalMel, syd: totalSyd, comb: totalComb } });
            }

            cards.sort((a, b) => b.unitTotal - a.unitTotal);

            // KPIs
            const totalUnique = (data.unique_student_count || 0); // Corrected key from backend
            const sCounts = data.status_counts || { Enrolled: 0, Other: 0 };
            const kpiEl = document.getElementById("kpiUnique");
            if (kpiEl) {
                kpiEl.innerHTML = `
            <div class="d-flex align-items-baseline gap-2">
                <span>${totalUnique.toLocaleString()}</span>
                <span class="text-secondary fw-normal fs-6" style="font-size:0.8rem !important">
                    (<span class="text-success fw-bold" title="Enrolled">${sCounts.Enrolled.toLocaleString()}</span> / 
                     <span class="text-muted" title="Admitted/Confirmed">${sCounts.Other.toLocaleString()}</span>)
                </span>
            </div>`;
            }
            // Trigger session label fetch for visible cards
            // Disabled temporarily to prevent HTTP 500 (Server Flood)
            // fetchSessionLabels();

            if (document.getElementById("kpiUnits")) document.getElementById("kpiUnits").innerText = unitCount.toLocaleString();
            
            async function fetchSessionLabels() {
                const btn = document.getElementById("btnLoadLabels");
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Loading...`;
                }

                const labels = document.querySelectorAll('.session-label');
                const queue = Array.from(labels).filter(l => !l.dataset.loaded);
                
                // Process in small chunks to avoid flooding
                const CHUNK_SIZE = 5;
                for (let i = 0; i < queue.length; i += CHUNK_SIZE) {
                    const chunk = queue.slice(i, i + CHUNK_SIZE);
                    await Promise.all(chunk.map(async (el) => {
                        const id = el.dataset.groupId;
                        if (!id) return;
                        
                        try {
                            // Call our proxy script
                            const res = await fetch(`scheduled_unit_sessions.php?id=${id}`);
                            const json = await res.json();
                            
                            // Expecting json to be an array of sessions. 
                            // We grab the first 'session_subject' that matches? 
                            // Or just the first one if it's the group ID.
                            if (Array.isArray(json) && json.length > 0) {
                                // Find a subject that looks useful, or just take the first one
                                const subject = json[0].session_subject || json[0].subject || "";
                                if (subject && subject !== "null") {
                                    el.innerText = subject;
                                    el.style.display = "block";
                                }
                            }
                        } catch (e) {
                            console.warn("Failed to fetch session for " + id, e);
                        } finally {
                            el.dataset.loaded = "true";
                        }
                    }));
                }
                
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Done`;
                    setTimeout(() => {
                         btn.innerHTML = `<i class="bi bi-tags-fill me-1"></i> Load Names`;
                    }, 3000);
                }
            }

            // Render Cards
            const grid = document.getElementById("cardsGrid");
            if (grid) {
                grid.innerHTML = "";
                if (cards.length === 0) {
                    grid.innerHTML = `<div class="col-12 text-center text-muted py-5">No units found.</div>`;
                } else {
                    cards.forEach(card => {
                        const blockHtml = card.blockList.map(b => {

                            // Render Groups
                            const groupsHtml = b.groups.map(g => {
                                const warn = (g.enrolled_count <= 10) ? ' <span class="text-danger fw-bold">!</span>' : '';

                                let badgeClass = "bg-secondary";
                                let progressClass = "bg-primary";
                                if (g.campus === 'MEL') { badgeClass = "bg-msg-mel text-dark border-info"; progressClass = "bg-info"; }
                                if (g.campus === 'SYD') { badgeClass = "bg-msg-syd text-dark border-danger"; progressClass = "bg-danger"; }
                                if (g.campus === 'COMB') { badgeClass = "bg-warning text-dark border-warning"; progressClass = "bg-warning"; }

                                // Lecturer & ID
                                const lecturer = g.lecturer ? `<div class="small text-secondary fst-italic mt-1"><i class="bi bi-person-circle me-1"></i>${g.lecturer}</div>` : '';
                                const isSynthetic = g.is_synthetic;
                                const groupIdDisplay = isSynthetic ? '' : `<span class="text-muted small ms-2" style="font-size:0.7rem;">#${g.id}</span>`;

                                // Placeholder for dynamic session label
                                const sessionLabelId = `session-label-${g.id}`;
                                const sessionLabel = isSynthetic ? '' : `<div id="${sessionLabelId}" class="small text-primary fw-bold mt-1 session-label" data-group-id="${g.id}" style="display:none;"></div>`;

                                // Capacity Bar
                                let progHtml = "";
                                if (g.capacity > 0) {
                                    const pct = Math.min(100, Math.round((g.enrolled_count / g.capacity) * 100));
                                    let color = progressClass;
                                    if (pct > 95) color = "bg-danger";

                                    progHtml = `
                                    <div class="d-flex align-items-center gap-2 mt-1" style="font-size:0.7rem;">
                                        <div class="progress flex-grow-1" style="height:4px;">
                                            <div class="progress-bar ${color}" role="progressbar" style="width: ${pct}%"></div>
                                        </div>
                                        <span class="text-muted">${g.enrolled_count}/${g.capacity}</span>
                                    </div>`;
                                }

                                return `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge ${badgeClass} bg-opacity-25 border me-2" style="min-width:45px;">${g.campus}</span>
                                            <span class="fw-bold text-dark small">${g.enrolled_count} students</span>
                                            ${warn}
                                            ${groupIdDisplay}
                                        </div>
                                    </div>
                                    ${sessionLabel}
                                    ${lecturer}
                                    ${progHtml}
                                </div>`;
                            }).join("");

                            return `
                            <div class="block-section mb-3">
                                <h6 class="text-uppercase text-secondary fw-bold small border-bottom pb-1 mb-2">${b.name}</h6>
                                <div class="ps-1">
                                    ${groupsHtml}
                                </div>
                            </div>
                        `;
                        }).join("");

                        let summaryHtml = '';
                        if (card.breakdown) {
                            summaryHtml += '<div class="mb-3 d-flex justify-content-center gap-2">';
                            // Using the same badge classes for consistency
                            if (card.breakdown.mel > 0) summaryHtml += `<span class="badge bg-msg-mel text-dark border border-info">MEL: ${card.breakdown.mel}</span>`;
                            if (card.breakdown.syd > 0) summaryHtml += `<span class="badge bg-msg-syd text-dark border border-danger">SYD: ${card.breakdown.syd}</span>`;
                            if (card.breakdown.comb > 0) summaryHtml += `<span class="badge bg-warning text-dark border border-warning">COMB: ${card.breakdown.comb}</span>`;
                            summaryHtml += '</div>';
                        }

                        const div = document.createElement("div");
                        div.className = "col-md-6 col-lg-4 mb-4 fade-in-up";
                        div.innerHTML = `
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 pt-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title text-success fw-bold mb-0">${card.unitCode}</h5>
                            <span class="badge bg-success">${card.unitTotal} Students</span>
                        </div>
                        <div class="card-body pt-0">
                            ${summaryHtml}
                            <div class="mt-3">
                                ${blockHtml}
                            </div>
                        </div>
                    </div>`;
                        grid.appendChild(div);
                    });
                }
            }

            // Inactive Units
            const closedTbody = document.querySelector("#closedTable tbody");
            if (closedTbody) {
                closedTbody.innerHTML = "";
                const closedGroups = expectedGroups.filter(g => !activeUnitCodes.has(g.eduOtherUnitId || ""));
                closedGroups.sort((a, b) => (a.eduOtherUnitId || "").localeCompare(b.eduOtherUnitId || ""));

                document.getElementById("closedCount").innerText = closedGroups.length;

                if (closedGroups.length === 0) {
                    closedTbody.innerHTML = `<tr><td colspan="2" class="text-center py-3">None found.</td></tr>`;
                } else {
                    for (const g of closedGroups) {
                        const code = g.eduOtherUnitId || "Unknown";
                        if (search && !code.toLowerCase().includes(search)) continue;
                        const blockLabel = (g.block && g.block !== "Unknown Block") ? g.block : "Inactive";
                        const tr = document.createElement("tr");
                        tr.innerHTML = `<td><span class="fw-bold text-dark">${code}</span></td><td>${blockLabel}</td>`;
                        closedTbody.appendChild(tr);
                    }
                }
            }

            // Risk Table (Sidebar)
            const riskTbody = document.querySelector("#riskTable tbody");
            if (riskTbody) {
                riskTbody.innerHTML = "";
                riskItems.sort((a, b) => a.count - b.count);
                document.getElementById("riskCount").innerText = riskItems.length;

                if (riskItems.length === 0) {
                    riskTbody.innerHTML = `<tr><td colspan="3" class="text-center py-3">None.</td></tr>`;
                } else {
                    riskItems.forEach(item => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `<td><b>${item.unitCode}</b></td><td>${item.blockName} (${item.campus})</td><td class="text-danger fw-bold">${item.count}</td>`;
                        riskTbody.appendChild(tr);
                    });
                }
            }
        }

        function renderSuggestions(data) {
            const tableBody = document.querySelector("#suggestionTable tbody");
            if (!tableBody || !data.risk_data) return;

            // grouping: Key = Course + Campus
            const groups = {};

            data.risk_data.forEach(s => {
                // Check if risk contains "Visa"
                if (s.risk && s.risk.includes("Visa")) {
                    const course = s.course || "Unknown Course";
                    const campus = s.campus || "Unknown";

                    const key = `${course}|${campus}`;
                    if (!groups[key]) {
                        groups[key] = { course, campus, count: 0, studentNames: [] };
                    }
                    groups[key].count++;
                }
            });

            // Convert to array and sort
            const sorted = Object.values(groups).sort((a, b) => b.count - a.count);

            tableBody.innerHTML = "";
            if (sorted.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4">No early-completion risks detected.</td></tr>`;
                return;
            }

            sorted.forEach(g => {
                const tr = document.createElement("tr");
                const suggestion = `Consider creating a <strong>fast-track group</strong> for ${g.course} in ${g.campus}.`;

                tr.innerHTML = `
                    <td class="fw-bold text-primary">${g.course}</td>
                    <td><span class="badge ${g.campus === 'MEL' ? 'badge-mel' : 'badge-syd'}">${g.campus}</span></td>
                    <td class="fw-bold fs-5">${g.count}</td>
                    <td class="text-success">${suggestion}</td>
                `;
                tableBody.appendChild(tr);
            });
        }


        function renderAtRisk(data) {
            const tableBody = document.querySelector("#atRiskTable tbody");
            if (!tableBody || !data.risk_data) return;

            // Filter checks
            const sarItems = data.risk_data.filter(s => {
                const r = (s.risk || "").toLowerCase();
                return r.includes("academic") || r.includes("status") || r.includes("encumbered");
            });

            tableBody.innerHTML = "";
            if (sarItems.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-success">No At-Risk or Encumbered students found.</td></tr>`;
                return;
            }

            // Sort by risk type (Status first, then Academic)
            sarItems.sort((a, b) => a.risk.localeCompare(b.risk));

            sarItems.forEach(s => {
                const tr = document.createElement("tr");
                let badgeClass = "bg-warning text-dark";
                let category = "Academic Risk";

                if (s.risk.toLowerCase().includes("encumbered")) {
                    badgeClass = "bg-danger text-white";
                    category = "Encumbrance";
                }

                tr.innerHTML = `
                    <td class="fw-bold">${s.name} <div class="small text-muted">${s.id}</div></td>
                    <td>
                        <div class="mb-1">${s.course || 'Unknown Course'}</div>
                        <span class="badge ${s.campus === 'MEL' ? 'badge-mel' : 'badge-syd'}">${s.campus || 'UNK'}</span>
                    </td>
                    <td><span class="badge ${badgeClass}">${category}</span></td>
                    <td class="text-danger small">${s.risk}</td>
                `;
                tableBody.appendChild(tr);
            });
        }

        function renderRetention(data) {
            const container = document.getElementById("retentionFlow");
            if (!container || !data.retention_data) return;

            // Sort blocks chronologically (simplistic mapping)
            const map = { "Summer School": 1, "Block 1": 2, "Block 2": 3, "Block 3": 4, "Block 4": 5, "Winter School": 6 };
            const blocks = Object.keys(data.retention_data).sort((a, b) => (map[a] || 99) - (map[b] || 99));

            if (blocks.length < 2) {
                container.innerHTML = "<div>Not enough distinct blocks to calculate flow yet.</div>";
                return;
            }

            let html = "";

            for (let i = 0; i < blocks.length; i++) {
                const currName = blocks[i];
                const currIds = data.retention_data[currName] || [];
                const count = currIds.length;

                // Block Node
                html += `
                <div class="text-center">
                    <div class="fw-bold text-secondary mb-1">${currName}</div>
                    <div class="bg-white border rounded-3 py-3 px-4 shadow-sm" style="min-width:120px;">
                        <div class="h3 fw-bold text-dark mb-0">${count}</div>
                        <div class="small text-muted">Students</div>
                    </div>
                </div>`;

                // Connector Arrow (Retention to Next Block)
                if (i < blocks.length - 1) {
                    const nextName = blocks[i + 1];
                    const nextIds = data.retention_data[nextName] || [];

                    // Intersection
                    const retentionCount = currIds.filter(id => nextIds.includes(id)).length;
                    const pct = count > 0 ? Math.round((retentionCount / count) * 100) : 0;

                    html += `
                    <div class="text-center px-2">
                        <div class="text-success fw-bold small mb-1">${pct}% Retained</div>
                        <div style="height:2px; width:60px; background:#198754; position:relative; top:5px;"></div>
                        <div class="text-muted small mt-2">(${retentionCount})</div>
                    </div>`;
                }
            }

            container.innerHTML = html;
        }

        const btnRefresh = document.getElementById("btnRefresh");
        if (btnRefresh) btnRefresh.addEventListener("click", () => loadData());
        const searchInput = document.getElementById("searchInput");
        if (searchInput) searchInput.addEventListener("input", () => render(globalData));
        window.addEventListener('DOMContentLoaded', () => { loadData(); });
    </script>
</body>

</html>