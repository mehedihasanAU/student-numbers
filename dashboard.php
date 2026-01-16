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

        /* Sky Blue */
        .badge-syd {
            background: #fce7f3;
            color: #be185d;
        }

        /* Pink/Rose */
        .badge-comb {
            background: #f3e8ff;
            color: #7e22ce;
        }

        /* Purple */

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
            <div class="col-8">
                <!--<img src="https://as.aih.edu.au/logo-green.svg" alt="AIHE Logo" style="height: 50px;"> -->
                <!-- <h4 class="mt-3 text-secondary fw-bold" style="color: #036A37 !important;">Enrolment Insights</h4> -->
            </div>
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
                    <div class="bg-light rounded-circle p-3 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
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
                    <div class="bg-light rounded-circle p-3 text-secondary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wide"
                            title="Total number of active class blocks being taught">Total Groups (Open+Closed)</div>
                        <div class="display-6 fw-bold text-dark mt-1" id="kpiGroups">—</div>
                    </div>
                    <div class="bg-light rounded-circle p-3 text-secondary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-3 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wide">Data Source</div>
                        <div class="h5 fw-bold text-dark mt-2 mb-0">Report 11472</div>
                        <div class="small text-muted" id="metaSourceDate">Active</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content (Left) -->
            <div class="col-lg-9">
                <!-- Content Grid -->
                <div id="cardsGrid" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <!-- Cards injected via JS -->
                </div>
            </div>

            <!-- Sidebar (Right) -->
            <div class="col-lg-3">
                <div class="sticky-top" style="top: 90px; z-index: 100;">

                    <!-- At Risk Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-secondary fw-bold">At Risk (<= 10)</h6>
                                    <span class="badge bg-danger text-white" id="riskCount">0</span>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
                                    <table class="table table-sm table-hover mb-0" id="riskTable"
                                        style="font-size: 0.8rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <!-- Column Filters -->
                                                <th>
                                                    Unit
                                                    <input type="text"
                                                        class="form-control form-control-sm border-0 bg-transparent p-0 fw-normal small-placeholder"
                                                        placeholder="Filter..." id="filtRiskUnit"
                                                        style="font-size:0.75rem; height:auto;">
                                                </th>
                                                <th>
                                                    Block
                                                    <input type="text"
                                                        class="form-control form-control-sm border-0 bg-transparent p-0 fw-normal small-placeholder"
                                                        placeholder="Filter..." id="filtRiskBlock"
                                                        style="font-size:0.75rem; height:auto;">
                                                </th>
                                                <th>
                                                    Prog
                                                    <input type="text"
                                                        class="form-control form-control-sm border-0 bg-transparent p-0 fw-normal small-placeholder"
                                                        placeholder="Filter..." id="filtRiskProg"
                                                        style="font-size:0.75rem; height:auto;">
                                                </th>
                                                <th class="text-end">
                                                    #
                                                    <input type="text"
                                                        class="form-control form-control-sm border-0 bg-transparent p-0 fw-normal small-placeholder text-end"
                                                        placeholder="#" id="filtRiskCount"
                                                        style="font-size:0.75rem; height:auto;">
                                                </th>
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
            const timeoutId = setTimeout(() => controller.abort(), 300000); // 300s timeout

            try {
                const res = await fetch(`${API_URL}?t=${Date.now()}`, { signal: controller.signal });
                clearTimeout(timeoutId);

                if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}`);

                const json = await res.json();
                globalData = json;
                render(json);

                // Check if we have partial data (source='pending' or 'report_only')
                // If so, trigger another fetch immediately
                if (json.groups) {
                    const pendingCount = json.groups.filter(g => g.source === 'pending' || g.source === 'report_only').length;
                    if (pendingCount > 0) {
                        console.log(`Partial data loaded (${pendingCount} pending). Auto-refreshing...`);
                        if (loader) {
                            loader.style.display = "flex";
                            // Update loader text to show progress
                            const txt = loader.querySelector(".fw-semibold");
                            if (txt) txt.innerText = `Loading... (${pendingCount} remaining)`;
                        }
                        setTimeout(loadData, 500); // 500ms delay then retry
                        return; // Don't hide loader yet
                    }
                }

            } catch (e) {
                console.error(e);
                if (e.name === 'AbortError') {
                    alert("Timeout: Server took too long to respond. Try refreshing.");
                } else {
                    alert("Error loading data: " + e.message);
                }
            } finally {
                // Only hide loader if we didn't trigger a recursive call
                // We know we triggered recursion if loader.style.display is still 'flex' and we're inside the success block
                // Simpler: check if we are *not* pending.
                const stillLoading = (globalData && globalData.groups && globalData.groups.some(g => g.source === 'pending' || g.source === 'report_only'));
                if (!stillLoading && loader) loader.style.display = "none";
            }
        }

        function getProgram(courseName, unitCode) {
            if (courseName) {
                const c = courseName.toLowerCase();
                if (c.includes("business information systems")) return "ISY";
                if (c.includes("accounting")) return "ACC";
            }

            // Fallback: Check Unit Code Prefix
            if (unitCode) {
                const u = unitCode.toUpperCase();
                if (u.startsWith("MBIS") || u.startsWith("BBIS") || u.includes("ISY")) return "ISY";
                if (u.startsWith("ACC") || u.includes("ACC")) return "ACC";
                if (u.startsWith("BUS")) return "BUS";
            }

            return "BUS"; // Default catch-all
        }

        function getGroupLabel(blockName) {
            const m = blockName.match(/Block\s*(\d+)/i);
            if (m) return `Group ${m[1]}`;
            return "Group 1";
        }

        function render(data) {
            if (!data) return;

            const detailed = data.campus_breakdown_detail || {};
            const unitMeta = data.meta || {};
            const expectedGroups = data.groups || [];

            // Calculate KPIs
            let grandTotal = 0;
            let unitCount = 0;
            let totalScheduledGroups = expectedGroups.length || 0;
            const cards = [];
            const activeUnitCodes = new Set();
            const riskItems = [];

            const searchInput = document.getElementById("searchInput");
            const search = searchInput ? searchInput.value.trim().toLowerCase() : "";

            // Filters for Risk Table
            const filtRiskUnit = document.getElementById("filtRiskUnit");
            const fRUnit = filtRiskUnit ? filtRiskUnit.value.toLowerCase() : "";

            const filtRiskBlock = document.getElementById("filtRiskBlock");
            const fRBlock = filtRiskBlock ? filtRiskBlock.value.toLowerCase() : "";

            const filtRiskProg = document.getElementById("filtRiskProg");
            const fRProg = filtRiskProg ? filtRiskProg.value.toLowerCase() : "";

            const filtRiskCount = document.getElementById("filtRiskCount");
            const fRCount = filtRiskCount ? filtRiskCount.value.toLowerCase() : "";

            // Iterate over units
            for (const [unitCode, blocks] of Object.entries(detailed)) {
                if (unitCode === "MATERIAL_FEE") continue;

                // Initialize sums
                let unitTotal = 0;
                const blockList = [];

                // Pre-calculate unit metadata
                const uMeta = unitMeta[unitCode] || {};
                const prog = getProgram(uMeta.course_name, unitCode);

                let totalMel = 0;
                let totalSyd = 0;
                let totalComb = 0;

                for (const [blockName, campuses] of Object.entries(blocks)) {
                    const mel = campuses["MEL"] || 0;
                    const syd = campuses["SYD"] || 0;
                    const comb = campuses["COMB"] || 0;
                    const subTotal = mel + syd + comb;

                    unitTotal += subTotal;
                    totalMel += mel;
                    totalSyd += syd;
                    totalComb += comb;

                    blockList.push({
                        name: blockName,
                        mel,
                        syd,
                        comb,
                        total: subTotal
                    });

                    // Risk Logic (<= 10)
                    if (mel > 0 && mel <= 10) riskItems.push({ unitCode, grpLabel: getGroupLabel(blockName), blockName, campus: "MEL", count: mel, prog });
                    if (syd > 0 && syd <= 10) riskItems.push({ unitCode, grpLabel: getGroupLabel(blockName), blockName, campus: "SYD", count: syd, prog });
                    if (comb > 0 && comb <= 10) riskItems.push({ unitCode, grpLabel: getGroupLabel(blockName), blockName, campus: "COMB", count: comb, prog });
                }

                grandTotal += unitTotal;
                unitCount++;
                activeUnitCodes.add(unitCode);

                // Main Search Filter (applies to Cards only)
                const hay = (unitCode + " " + blockList.map(b => b.name).join(" ")).toLowerCase();
                if (search && !hay.includes(search)) continue;

                blockList.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
                cards.push({ unitCode, unitTotal, blockList, breakdown: { mel: totalMel, syd: totalSyd, comb: totalComb } });
            }

            cards.sort((a, b) => b.unitTotal - a.unitTotal);

            // KPIs
            const totalUnique = (data.unique_students || 0);
            const sCounts = data.status_counts || { Enrolled: 0, Other: 0 };

            // Total Students Card
            // Display "Total (Enrolled: X, Validated: Y)"
            const kpiEl = document.getElementById("kpiUnique");
            if (kpiEl) {
                const enr = sCounts.Enrolled || 0;
                const oth = sCounts.Other || 0;

                kpiEl.innerHTML = `
            <div class="d-flex align-items-baseline gap-2">
                <span>${totalUnique.toLocaleString()}</span>
                <span class="text-secondary fw-normal fs-6" style="font-size:0.8rem !important">
                    (<span class="text-success fw-bold" title="Enrolled">${enr.toLocaleString()}</span> / 
                     <span class="text-muted" title="Admitted/Confirmed">${oth.toLocaleString()}</span>)
                </span>
            </div>
        `;
            }

            if (document.getElementById("kpiUnits")) document.getElementById("kpiUnits").innerText = unitCount.toLocaleString();
            if (document.getElementById("kpiGroups")) document.getElementById("kpiGroups").innerText = totalScheduledGroups.toLocaleString();

            // Render Cards
            const grid = document.getElementById("cardsGrid");
            if (grid) {
                grid.innerHTML = "";
                if (cards.length === 0) {
                    grid.innerHTML = `<div class="col-12 text-center text-muted py-5">No units found.</div>`;
                } else {
                    cards.forEach(card => {
                        const blockHtml = card.blockList.map(b => {
                            const warn = (n) => (n <= 10) ? ' <span class="text-danger fw-bold">!</span>' : '';
                            return `
                    <div class="block-row">
                        <span class="block-name">${b.name}</span>
                        <div>
                            ${b.mel > 0 ? `<span class="campus-badge badge-mel" title="Melbourne">MEL: ${b.mel}${warn(b.mel)}</span>` : ''}
                            ${b.syd > 0 ? `<span class="campus-badge badge-syd" title="Sydney">SYD: ${b.syd}${warn(b.syd)}</span>` : ''}
                            ${b.comb > 0 ? `<span class="campus-badge badge-comb" title="SYD/MEL Combined">COMB: ${b.comb}${warn(b.comb)}</span>` : ''}
                        </div>
                    </div>
                `;
                        }).join("");

                        // Summary Badge Logic
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
                            <!-- Summary Badges -->
                            ${summaryHtml}
                            
                            <div class="mt-2 text-start">
                                ${blockHtml}
                            </div>
                        </div>
                    </div>
                `;
                        grid.appendChild(div);
                    });
                }
            }

            // 4. Inactive Units/Groups Logic
            const closedTbody = document.querySelector("#closedTable tbody");
            if (closedTbody) {
                closedTbody.innerHTML = "";

                // Filter groups where the Unit Code has ZERO active enrolments
                const closedGroups = expectedGroups.filter(g => {
                    const code = g.eduOtherUnitId || "";
                    return !activeUnitCodes.has(code);
                });

                // Sort by Unit Code
                closedGroups.sort((a, b) => (a.eduOtherUnitId || "").localeCompare(b.eduOtherUnitId || ""));

                const countEl = document.getElementById("closedCount");
                if (countEl) countEl.innerText = closedGroups.length;

                if (closedGroups.length === 0) {
                    closedTbody.innerHTML = `<tr><td colspan="2" class="text-center py-3">None found.</td></tr>`;
                } else {
                    for (const g of closedGroups) {
                        const code = g.eduOtherUnitId || "Unknown";
                        if (search && !code.toLowerCase().includes(search)) continue;

                        let blockLabel = g.block || "";
                        let campus = g.campus || "";

                        // Clean up "Unknown Block" display
                        if (!blockLabel || blockLabel === "Unknown Block") {
                            blockLabel = "Inactive";
                        }

                        // If campus is unknown/missing, don't show ()
                        if (!campus || campus === "Unknown") campus = "";

                        const grpLabel = getGroupLabel(blockLabel);

                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                    <td class="fw-bold"><span class="text-dark">${code}</span> <small class="text-muted ms-1">${grpLabel}</small></td>
                    <td><small>${blockLabel} ${campus ? `(${campus})` : ''}</small></td>
                 `;
                        closedTbody.appendChild(tr);
                    }
                }
            }

            // Render Risk Table
            const riskTbody = document.querySelector("#riskTable tbody");
            if (riskTbody) {
                riskTbody.innerHTML = "";

                const visibleRisk = riskItems.filter(item => {
                    if (fRUnit && !item.unitCode.toLowerCase().includes(fRUnit)) return false;
                    if (fRBlock && !item.blockName.toLowerCase().includes(fRBlock)) return false;
                    if (fRProg && !item.prog.toLowerCase().includes(fRProg)) return false;
                    if (fRCount && String(item.count) !== fRCount) return false;
                    return true;
                });

                const riskCountEl = document.getElementById("riskCount");
                if (riskCountEl) riskCountEl.innerText = visibleRisk.length;

                // Sort by count asc by default
                visibleRisk.sort((a, b) => a.count - b.count);

                if (visibleRisk.length === 0) {
                    riskTbody.innerHTML = `<tr><td colspan="4" class="text-center py-3">None.</td></tr>`;
                } else {
                    for (const item of visibleRisk) {
                        let progBadge = `<span class="badge bg-primary" style="font-size:0.65rem">BUS</span>`; // Default Blue for BUS

                        if (item.prog === 'ISY') {
                            progBadge = `<span class="badge bg-success" style="font-size:0.65rem">ISY</span>`; // Green
                        } else if (item.prog === 'ACC') {
                            progBadge = `<span class="badge text-white" style="font-size:0.65rem; background-color:#BD2200;">ACC</span>`; // Red
                        } else {
                            // BUS or UNK (Default Blue)
                            progBadge = `<span class="badge bg-primary" style="font-size:0.65rem">${item.prog}</span>`;
                        }

                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                    <td class="fw-bold" style="white-space:nowrap;">
                        ${item.unitCode} <span class="text-muted small">${item.grpLabel}</span>
                    </td>
                    <td><small style="font-size:0.75rem">${item.blockName} (${item.campus})</small></td>
                    <td>${progBadge}</td>
                    <td class="text-end fw-bold text-danger">${item.count}</td>
                 `;
                        riskTbody.appendChild(tr);
                    }
                }
            }
        }

        // Events
        const btnRefresh = document.getElementById("btnRefresh");
        if (btnRefresh) btnRefresh.addEventListener("click", () => loadData());

        const searchInput = document.getElementById("searchInput");
        if (searchInput) searchInput.addEventListener("input", () => render(globalData));

        // Table Filters
        ['filtRiskUnit', 'filtRiskBlock', 'filtRiskProg', 'filtRiskCount'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener("input", () => render(globalData));
        });

        // Init
        window.addEventListener('DOMContentLoaded', () => {
            loadData();
        });
    </script>
</body>

</html>