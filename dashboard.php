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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --aihe-green: #036A37;
            --aihe-green-soft: rgba(3, 106, 55, 0.08);
            --bg-body: #f5f7fa;
            --card-border: rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: #1f2937;
        }

        .navbar {
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            color: #111 !important;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02), 0 8px 16px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 12px 24px rgba(0, 0, 0, 0.05);
        }

        .unit-code {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111;
        }

        .unit-total {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--aihe-green);
            background: var(--aihe-green-soft);
            padding: 4px 10px;
            border-radius: 20px;
        }

        .block-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .block-row:last-child {
            border-bottom: none;
        }

        .block-name {
            font-weight: 500;
            font-size: 0.95rem;
            color: #4b5563;
        }

        .lecturer-name {
            font-size: 0.8rem;
            color: #6b7280;
            font-style: italic;
        }

        .campus-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            margin-left: 6px;
        }

        .badge-mel {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-syd {
            background: #fce7f3;
            color: #be185d;
        }

        .badge-comb {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .search-input {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            border-color: var(--aihe-green);
            box-shadow: 0 0 0 3px var(--aihe-green-soft);
        }

        /* Loader */
        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .progress-thin {
            height: 6px;
            margin-top: 4px;
            border-radius: 3px;
            background-color: #e5e7eb;
        }
    </style>
</head>

<body>

    <!-- Loader -->
    <div id="loader" class="loader-overlay">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <div class="fw-semibold text-secondary">Please wait. Loading live data...</div>
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

        async function loadData() {
            const loader = document.getElementById("loader");
            if (loader) loader.style.display = "flex";
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 300000);

            try {
                const res = await fetch(`${API_URL}?t=${Date.now()}`, { signal: controller.signal });
                clearTimeout(timeoutId);
                if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}`);
                const json = await res.json();
                globalData = json;
                render(json);
                renderSuggestions(json);
                renderAtRisk(json);
                renderRetention(json);

                if (json.groups) {
                    const pendingCount = json.groups.filter(g => g.source === 'pending').length;
                    if (pendingCount > 0) {
                        if (loader) loader.querySelector(".fw-semibold").innerText = `Loading... (${pendingCount} remaining)`;
                        setTimeout(loadData, 1000);
                        return;
                    }
                }
            } catch (e) {
                console.error(e);
                alert("Error: " + e.message);
            } finally {
                const stillLoading = (globalData && globalData.groups && globalData.groups.some(g => g.source === 'pending'));
                if (!stillLoading && loader) loader.style.display = "none";
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

            const activeUnitCodes = new Set(); // Re-added to fix ReferenceError

            const search = document.getElementById("searchInput") ? document.getElementById("searchInput").value.trim().toLowerCase() : "";

            // Iterate Units
            for (const [unitCode, blocks] of Object.entries(unitGroups)) {
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

                        return {
                            ...g,
                            label: `Group (ID: ${g.id || 'N/A'})` // Can be refined if we have 'Group 1' etc from name
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
            if (document.getElementById("kpiUnits")) document.getElementById("kpiUnits").innerText = unitCount.toLocaleString();

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
                                const campusClass = g.campus === 'MEL' ? 'badge-mel' : (g.campus === 'SYD' ? 'badge-syd' : 'badge-comb');
                                const lecturer = g.lecturer ? ` - <span class="text-muted fst-italic">${g.lecturer}</span>` : '';

                                // Capacity Bar
                                let progHtml = "";
                                if (g.capacity > 0) {
                                    const pct = Math.min(100, Math.round((g.enrolled_count / g.capacity) * 100));
                                    let color = "bg-primary";
                                    if (pct > 90) color = "bg-danger";
                                    else if (pct > 75) color = "bg-warning";
                                    else if (pct < 30) color = "bg-info";

                                    progHtml = `
                                    <div class="d-flex align-items-center gap-2 mt-1" style="font-size:0.75rem;">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar ${color}" role="progressbar" style="width: ${pct}%"></div>
                                        </div>
                                        <span class="fw-bold text-dark">${g.enrolled_count}/${g.capacity}</span>
                                    </div>`;
                                }

                                return `
                                <div class="mb-2 pb-2 border-bottom border-light">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="campus-badge ${campusClass}">${g.campus}</span>
                                            <span class="small fw-bold ms-1 text-dark">${g.enrolled_count} students</span>
                                            ${warn}
                                            <span class="small">${lecturer}</span>
                                        </div>
                                    </div>
                                    ${progHtml}
                                </div>`;
                            }).join("");

                            return `
                            <div class="block-section mb-3">
                                <div class="fw-bold text-secondary small text-uppercase mb-2" style="letter-spacing:0.5px;">${b.name}</div>
                                <div class="ps-2 border-start border-3 border-light">
                                    ${groupsHtml}
                                </div>
                            </div>
                        `;
                        }).join("");

                        let summaryHtml = '';
                        if (card.breakdown) {
                            summaryHtml += '<div class="mb-3 d-flex justify-content-center gap-2">';
                            if (card.breakdown.mel > 0) summaryHtml += `<span class="badge badge-mel border-0">MEL: ${card.breakdown.mel}</span>`;
                            if (card.breakdown.syd > 0) summaryHtml += `<span class="badge badge-syd border-0">SYD: ${card.breakdown.syd}</span>`;
                            if (card.breakdown.comb > 0) summaryHtml += `<span class="badge badge-comb border-0">COMB: ${card.breakdown.comb}</span>`;
                            summaryHtml += '</div>';
                        }

                        const div = document.createElement("div");
                        div.className = "col";
                        div.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="unit-code">${card.unitCode}</span>
                                <span class="unit-total">${card.unitTotal} Students</span>
                            </div>
                            ${summaryHtml}
                            <div class="mt-3 text-start small">
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