<?php
require_once __DIR__ . '/db.php';
// Ensure DB is initialized on page visit
get_db();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMMP - Student Graduand Task Selection Portal</title>
    <meta name="description" content="Mobile-friendly task selection manager for marking graduands and downloading matching template records.">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        },
                        dark: {
                            bg: '#0B0F19',
                            card: '#111827',
                            border: '#1F2937',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* Custom scrollbar & animations */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0B0F19; }
        ::-webkit-scrollbar-thumb { background: #1F2937; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
    </style>
</head>
<body class="bg-dark-bg text-gray-100 font-sans min-h-screen pb-24 selection:bg-brand-500 selection:text-white">

    <!-- Top Navigation / Header -->
    <header class="sticky top-0 z-40 bg-dark-bg/80 backdrop-blur-md border-b border-dark-border py-4 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center shadow-lg shadow-brand-500/20 text-white font-bold text-xl">
                    <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                        Graduands Selection Portal
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">2024 Index</span>
                    </h1>
                    <p class="text-xs text-gray-400">Mark students for template export</p>
                </div>
            </div>

            <!-- Portal Navigation Tabs -->
            <div class="flex items-center gap-1 bg-dark-card border border-dark-border p-1 rounded-xl">
                <a href="index.php" class="px-3.5 py-1.5 rounded-lg bg-brand-500 text-white text-xs font-semibold shadow flex items-center gap-1.5">
                    <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                    Index Selection
                </a>
                <a href="graduand.php" class="px-3.5 py-1.5 rounded-lg text-gray-400 hover:text-white text-xs font-medium transition flex items-center gap-1.5">
                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    Graduand Bio & Photo Portal
                </a>
            </div>

            <!-- Action CTAs -->
            <div class="flex items-center gap-2.5 w-full md:w-auto justify-end flex-wrap">
                <a href="graduand_api.php?action=export_csv" class="px-3.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 font-semibold shadow-sm transition flex items-center gap-1.5 text-xs group" title="Export Graduand Records to Excel/CSV">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-400"></i>
                    Export Graduands
                </a>
                <button type="button" id="btnOpenUploadModal" class="px-3.5 py-2 rounded-xl bg-dark-card hover:bg-gray-800 text-gray-200 border border-dark-border font-medium shadow-sm transition flex items-center gap-1.5 text-xs group">
                    <i data-lucide="upload" class="w-4 h-4 text-brand-400 group-hover:scale-110 transition"></i>
                    Upload Excel
                </button>
                <button type="button" id="btnDownload" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold shadow-lg shadow-emerald-500/25 active:scale-95 transition flex items-center justify-center gap-2 text-xs group">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 group-hover:rotate-6 transition"></i>
                    Download (<span id="statSelectedBtn">0</span>)
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-8 mt-6">

        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-blue-500/5 rounded-full blur-xl"></div>
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Total Students</span>
                    <i data-lucide="users" class="w-4 h-4 text-blue-400"></i>
                </div>
                <div class="text-2xl font-bold text-white" id="statTotal">-</div>
                <div class="text-[10px] text-gray-500 mt-1">In 2024 index record</div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-brand-500/10 rounded-full blur-xl"></div>
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Selected</span>
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-500"></i>
                </div>
                <div class="text-2xl font-bold text-brand-400" id="statSelected">-</div>
                <div class="w-full bg-gray-800 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div id="statProgressBar" class="bg-gradient-to-r from-brand-500 to-emerald-400 h-full w-0 transition-all duration-500"></div>
                </div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Fresh Candidates</span>
                    <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                </div>
                <div class="text-2xl font-bold text-amber-400" id="statFresh">-</div>
                <div class="text-[10px] text-gray-500 mt-1">Ready for remark</div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Resit Candidates</span>
                    <i data-lucide="rotate-cw" class="w-4 h-4 text-purple-400"></i>
                </div>
                <div class="text-2xl font-bold text-purple-400" id="statResit">-</div>
                <div class="text-[10px] text-gray-500 mt-1">Ready for remark</div>
            </div>
        </div>

        <!-- Filter & Actions Bar -->
        <div class="bg-dark-card border border-dark-border rounded-2xl p-4 mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 shadow-sm">
            
            <!-- Search Input -->
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="searchInput" placeholder="Search by full name or indexing number (e.g. B/130)..." class="w-full pl-10 pr-4 py-2 bg-dark-bg border border-dark-border rounded-xl text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
            </div>

            <!-- Filter Dropdowns -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
                <select id="filterStatus" class="bg-dark-bg border border-dark-border rounded-xl px-3 py-2 text-xs text-gray-300 focus:outline-none focus:border-brand-500 cursor-pointer">
                    <option value="all">Status: All</option>
                    <option value="selected">Selected Only</option>
                    <option value="unselected">Unselected Only</option>
                </select>

                <select id="filterCadre" class="bg-dark-bg border border-dark-border rounded-xl px-3 py-2 text-xs text-gray-300 focus:outline-none focus:border-brand-500 cursor-pointer">
                    <option value="">Cadre: All</option>
                </select>

                <select id="filterGender" class="bg-dark-bg border border-dark-border rounded-xl px-3 py-2 text-xs text-gray-300 focus:outline-none focus:border-brand-500 cursor-pointer">
                    <option value="">Gender: All</option>
                </select>
            </div>

            <!-- Bulk Actions -->
            <div class="flex items-center gap-2 border-t md:border-t-0 pt-3 md:pt-0 border-dark-border">
                <button type="button" id="btnSelectVisible" class="px-3 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-medium transition flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-brand-400"></i>
                    Select Filtered
                </button>
                <button type="button" id="btnDeselectVisible" class="px-3 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-medium transition flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="square" class="w-3.5 h-3.5 text-gray-400"></i>
                    Deselect Filtered
                </button>
                <button type="button" id="btnResetAll" class="px-3 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs font-medium transition ml-auto md:ml-0" title="Reset all selections to 0">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loader" class="py-16 flex flex-col items-center justify-center text-gray-400 gap-3 hidden">
            <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-brand-500"></i>
            <p class="text-xs">Loading student index records...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-16 flex flex-col items-center justify-center text-gray-500 gap-3 hidden">
            <div class="w-12 h-12 rounded-full bg-gray-800/50 flex items-center justify-center text-gray-600">
                <i data-lucide="search-x" class="w-6 h-6"></i>
            </div>
            <p class="text-sm font-medium text-gray-400">No matching student records found.</p>
            <p class="text-xs">Try clearing your search filters.</p>
        </div>

        <!-- MOBILE VIEW: Interactive Card Stack (< 768px) -->
        <div id="mobileGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:hidden"></div>

        <!-- DESKTOP VIEW: Data Table (>= 768px) -->
        <div id="desktopTableWrapper" class="hidden md:block bg-dark-card border border-dark-border rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-dark-bg/60 border-b border-dark-border text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            <th class="py-3 px-4 w-12 text-center">Select</th>
                            <th class="py-3 px-4 w-12 text-center">No.</th>
                            <th class="py-3 px-4">Full Name</th>
                            <th class="py-3 px-4">Indexing Number</th>
                            <th class="py-3 px-4">Cadre</th>
                            <th class="py-3 px-4">Gender</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Year</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-dark-border text-xs font-normal"></tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Upload Excel Modal -->
    <div id="uploadModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 relative shadow-2xl animate-fade-in">
            <button type="button" id="btnCloseUploadModal" class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400">
                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Upload Graduand Excel</h3>
                    <p class="text-xs text-gray-400">Add or update records in the graduand table</p>
                </div>
            </div>

            <form id="uploadExcelForm" class="space-y-4">
                <div id="dropZone" class="border-2 border-dashed border-dark-border hover:border-brand-500/50 rounded-2xl p-6 text-center cursor-pointer transition bg-dark-bg/50">
                    <input type="file" id="excelFileInput" name="excel_file" accept=".xlsx" class="hidden" required>
                    <i data-lucide="file-spreadsheet" class="w-8 h-8 text-brand-400 mx-auto mb-2"></i>
                    <p class="text-xs font-medium text-gray-200" id="fileNameDisplay">Click to select or drag and drop .xlsx file</p>
                    <p class="text-[10px] text-gray-500 mt-1">Accepts Excel (.xlsx) files formatted with columns: Exam No, Full Name, Cadre, Papers, Type</p>
                </div>

                <div id="uploadLoader" class="hidden py-3 flex items-center justify-center gap-2 text-brand-400 text-xs">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    <span>Parsing spreadsheet and updating database...</span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnCancelUpload" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-medium transition">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitUpload" class="px-5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold shadow-lg shadow-brand-500/20 transition flex items-center gap-1.5 disabled:opacity-50">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-16 opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-2.5 bg-dark-card border border-brand-500/30 text-white px-4 py-3 rounded-xl shadow-2xl">
        <i data-lucide="check-circle" class="w-4 h-4 text-brand-500"></i>
        <span id="toastMsg" class="text-xs font-medium">Saved</span>
    </div>

    <!-- App JavaScript Logic -->
    <script>
        $(document).ready(function() {
            let currentStudents = [];
            let searchTimeout = null;

            // Initialize Lucide Icons
            lucide.createIcons();

            // Load records initial
            fetchStudents();

            // Search event with debounce
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchStudents, 250);
            });

            // Filter dropdown changes
            $('#filterStatus, #filterCadre, #filterGender').on('change', fetchStudents);

            // Fetch students from API
            function fetchStudents() {
                $('#loader').removeClass('hidden');
                $('#emptyState').addClass('hidden');

                const params = {
                    action: 'get_students',
                    search: $('#searchInput').val(),
                    status: $('#filterStatus').val(),
                    cadre: $('#filterCadre').val(),
                    gender: $('#filterGender').val()
                };

                $.getJSON('api.php', params, function(res) {
                    $('#loader').addClass('hidden');
                    if (!res.success) {
                        showToast('Error loading data: ' + res.error, false);
                        return;
                    }

                    currentStudents = res.students;
                    updateStats(res.stats);
                    populateFilterDropdowns(res.filters);
                    renderRecords();
                }).fail(function() {
                    $('#loader').addClass('hidden');
                    showToast('Network error connecting to API.', false);
                });
            }

            // Populate Cadre / Gender filters once
            let filtersPopulated = false;
            function populateFilterDropdowns(filters) {
                if (filtersPopulated) return;
                filtersPopulated = true;

                const currCadre = $('#filterCadre').val();
                const currGen = $('#filterGender').val();

                filters.cadres.forEach(c => {
                    $('#filterCadre').append(`<option value="${c}">${c}</option>`);
                });
                filters.genders.forEach(g => {
                    $('#filterGender').append(`<option value="${g}">${g}</option>`);
                });

                if (currCadre) $('#filterCadre').val(currCadre);
                if (currGen) $('#filterGender').val(currGen);
            }

            // Update header and dashboard stat cards
            function updateStats(stats) {
                $('#statTotal').text(stats.total);
                $('#statSelected, #statSelectedBtn').text(stats.selected);
                $('#statFresh').text(stats.fresh);
                $('#statResit').text(stats.resit);

                const percent = stats.total > 0 ? Math.round((stats.selected / stats.total) * 100) : 0;
                $('#statProgressBar').css('width', percent + '%');
            }

            // Render both mobile card grid and desktop table
            function renderRecords() {
                const mobGrid = $('#mobileGrid');
                const tbBody = $('#tableBody');
                mobGrid.empty();
                tbBody.empty();

                if (currentStudents.length === 0) {
                    $('#emptyState').removeClass('hidden');
                    return;
                }

                currentStudents.forEach(stu => {
                    const isSel = stu.is_selected === 1;
                    const cardBg = isSel ? 'bg-brand-500/10 border-brand-500/40 shadow-brand-500/5' : 'bg-dark-card border-dark-border';
                    const rowBg = isSel ? 'bg-brand-500/5 font-medium' : 'hover:bg-dark-border/40';
                    const typeBadge = stu.type.toLowerCase() === 'fresh' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-purple-500/10 text-purple-400 border-purple-500/20';

                    // Mobile Card
                    const cardHtml = `
                        <div class="student-item cursor-pointer rounded-2xl border p-4 transition-all duration-200 active:scale-[0.98] relative ${cardBg}" data-id="${stu.id}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="stu-checkbox w-5 h-5 rounded-lg border-gray-600 text-brand-500 focus:ring-brand-500 bg-dark-bg cursor-pointer pointer-events-none" ${isSel ? 'checked' : ''}>
                                    <div>
                                        <h3 class="text-sm font-semibold text-white tracking-tight">${stu.full_name || 'Unnamed Student'}</h3>
                                        <p class="text-xs font-mono text-brand-400 mt-0.5">${stu.indexing}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border ${typeBadge} uppercase">${stu.type || 'Fresh'}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-dark-border/60 text-[11px] text-gray-400">
                                <span><strong class="text-gray-300">No:</strong> ${stu.sn}</span>
                                <span><strong class="text-gray-300">Cadre:</strong> ${stu.cadre || '-'}</span>
                                <span><strong class="text-gray-300">Gender:</strong> ${stu.gender || '-'}</span>
                                <span class="ml-auto text-gray-500">${stu.year}</span>
                            </div>
                        </div>
                    `;
                    mobGrid.append(cardHtml);

                    // Desktop Row
                    const rowHtml = `
                        <tr class="student-item cursor-pointer transition ${rowBg}" data-id="${stu.id}">
                            <td class="py-3 px-4 text-center" onclick="event.stopPropagation()">
                                <input type="checkbox" class="stu-checkbox w-4 h-4 rounded border-gray-600 text-brand-500 focus:ring-brand-500 bg-dark-bg cursor-pointer" ${isSel ? 'checked' : ''}>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-gray-400">${stu.sn}</td>
                            <td class="py-3 px-4 text-white font-medium">${stu.full_name || '-'}</td>
                            <td class="py-3 px-4 font-mono text-brand-400">${stu.indexing || '-'}</td>
                            <td class="py-3 px-4">${stu.cadre || '-'}</td>
                            <td class="py-3 px-4">${stu.gender || '-'}</td>
                            <td class="py-3 px-4">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded border ${typeBadge} uppercase">${stu.type || 'Fresh'}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-400">${stu.year || '-'}</td>
                        </tr>
                    `;
                    tbBody.append(rowHtml);
                });

                lucide.createIcons();
            }

            // Click event on student card/row
            $(document).on('click', '.student-item', function() {
                const id = $(this).data('id');
                toggleSelect(id);
            });

            // Checkbox direct click on desktop table
            $(document).on('click', '.stu-checkbox', function(e) {
                e.stopPropagation();
                const item = $(this).closest('.student-item');
                const id = item.data('id');
                toggleSelect(id);
            });

            // Toggle single selection via AJAX
            function toggleSelect(id) {
                $.post('api.php?action=toggle_select', { id: id }, function(res) {
                    if (res.success) {
                        // Update local array state
                        const stu = currentStudents.find(s => s.id == id);
                        if (stu) {
                            stu.is_selected = res.is_selected;
                        }
                        
                        // Re-fetch stats without full reload
                        refreshStatsOnly();
                        
                        // Toggle visual class on elements
                        $(`.student-item[data-id="${id}"]`).each(function() {
                            const isSel = res.is_selected === 1;
                            $(this).find('.stu-checkbox').prop('checked', isSel);
                            if ($(this).is('tr')) {
                                $(this).toggleClass('bg-brand-500/5 font-medium', isSel);
                            } else {
                                $(this).toggleClass('bg-brand-500/10 border-brand-500/40 shadow-brand-500/5', isSel);
                                $(this).toggleClass('bg-dark-card border-dark-border', !isSel);
                            }
                        });

                        showToast(res.is_selected === 1 ? 'Student marked as selected' : 'Student selection removed');
                    }
                });
            }

            // Refresh stats summary only
            function refreshStatsOnly() {
                $.getJSON('api.php?action=get_students&status=selected', function(res) {
                    if (res.success) {
                        updateStats(res.stats);
                    }
                });
            }

            // Bulk action: Select Filtered
            $('#btnSelectVisible').on('click', function() {
                const ids = currentStudents.map(s => s.id);
                if (ids.length === 0) return;

                $.post('api.php?action=bulk_select', { select: 1, ids: ids }, function(res) {
                    if (res.success) {
                        showToast(`Marked ${ids.length} visible records`);
                        fetchStudents();
                    }
                });
            });

            // Bulk action: Deselect Filtered
            $('#btnDeselectVisible').on('click', function() {
                const ids = currentStudents.map(s => s.id);
                if (ids.length === 0) return;

                $.post('api.php?action=bulk_select', { select: 0, ids: ids }, function(res) {
                    if (res.success) {
                        showToast(`Deselected ${ids.length} visible records`);
                        fetchStudents();
                    }
                });
            });

            // Reset all
            $('#btnResetAll').on('click', function() {
                if (!confirm("Are you sure you want to reset all student selections to 0?")) return;
                $.post('api.php?action=reset_all', function(res) {
                    if (res.success) {
                        showToast('All selections reset.');
                        fetchStudents();
                    }
                });
            });

            // Download CTA click
            $('#btnDownload').on('click', function() {
                const selCount = parseInt($('#statSelected').text()) || 0;
                if (selCount === 0) {
                    showToast('Please select at least 1 student before downloading.', false);
                    return;
                }
                window.location.href = 'download.php';
            });

            // Upload Excel Modal Interactivity
            $('#btnOpenUploadModal').on('click', function() {
                $('#uploadModal').removeClass('hidden');
            });
            $('#btnCloseUploadModal, #btnCancelUpload').on('click', function() {
                $('#uploadModal').addClass('hidden');
            });

            // File Dropzone click & drag
            $('#dropZone').on('click', function() {
                $('#excelFileInput').click();
            });
            $('#excelFileInput').on('change', function() {
                if (this.files && this.files[0]) {
                    $('#fileNameDisplay').text(this.files[0].name).addClass('text-brand-400 font-bold');
                }
            });

            // Upload Excel Form Submit
            $('#uploadExcelForm').on('submit', function(e) {
                e.preventDefault();
                const fileInput = $('#excelFileInput')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    showToast('Please select an Excel file first.', false);
                    return;
                }

                const formData = new FormData();
                formData.append('excel_file', fileInput.files[0]);
                formData.append('action', 'upload_graduand_excel');

                $('#uploadLoader').removeClass('hidden');
                $('#btnSubmitUpload').prop('disabled', true);

                $.ajax({
                    url: 'api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#uploadLoader').addClass('hidden');
                        $('#btnSubmitUpload').prop('disabled', false);
                        if (res.success) {
                            showToast(`Successfully imported/updated ${res.count} records in graduand table!`);
                            $('#uploadModal').addClass('hidden');
                            $('#uploadExcelForm')[0].reset();
                            $('#fileNameDisplay').text('Click to select or drag and drop .xlsx file').removeClass('text-brand-400 font-bold');
                        } else {
                            showToast(res.error || 'Failed to upload Excel file.', false);
                        }
                    },
                    error: function() {
                        $('#uploadLoader').addClass('hidden');
                        $('#btnSubmitUpload').prop('disabled', false);
                        showToast('Network error during upload.', false);
                    }
                });
            });

            // Toast helper
            function showToast(msg, isSuccess = true) {
                $('#toastMsg').text(msg);
                const toast = $('#toast');
                toast.removeClass('border-red-500/30 text-red-300 border-brand-500/30 text-white');
                if (!isSuccess) {
                    toast.addClass('border-red-500/50 text-red-200');
                } else {
                    toast.addClass('border-brand-500/30 text-white');
                }
                toast.removeClass('translate-y-16 opacity-0').addClass('translate-y-0 opacity-100');
                setTimeout(() => {
                    toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-16 opacity-0');
                }, 2500);
            }
        });
    </script>
</body>
</html>
