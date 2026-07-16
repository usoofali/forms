<?php
require_once __DIR__ . '/db.php';
// Ensure DB is initialized
get_db();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMMP - Graduand Bio & Photo Upload Portal</title>
    <meta name="description" content="Student self-service portal for updating Date of Birth, Blood Group, and uploading passport photograph with Cropper.js sizing.">
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
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0B0F19; }
        ::-webkit-scrollbar-thumb { background: #1F2937; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
        .cropper-view-box, .cropper-face {
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-dark-bg text-gray-100 font-sans min-h-screen pb-24 selection:bg-brand-500 selection:text-white">

    <!-- Top Navigation / Header -->
    <header class="sticky top-0 z-40 bg-dark-bg/80 backdrop-blur-md border-b border-dark-border py-4 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 text-white font-bold text-xl">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                        Graduand Bio & Photo Portal
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Self-Service</span>
                    </h1>
                    <p class="text-xs text-gray-400">Click your name or index record to upload your passport and biodata</p>
                </div>
            </div>

            <!-- Portal Navigation Tabs -->
            <div class="flex items-center gap-1 bg-dark-card border border-dark-border p-1 rounded-xl">
                <a href="graduand.php" class="px-3.5 py-1.5 rounded-lg bg-brand-500 text-white text-xs font-semibold shadow flex items-center gap-1.5">
                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                    Graduand Bio & Photo Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-8 mt-6">

        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-emerald-500/5 rounded-full blur-xl"></div>
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Total Fresh Candidates</span>
                    <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i>
                </div>
                <div class="text-2xl font-bold text-white" id="statTotalFresh">-</div>
                <div class="text-[10px] text-gray-500 mt-1">Eligible for bio & passport update</div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-brand-500/10 rounded-full blur-xl"></div>
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Completed Updates</span>
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-500"></i>
                </div>
                <div class="text-2xl font-bold text-brand-400" id="statCompletedFresh">-</div>
                <div class="w-full bg-gray-800 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div id="statProgressBar" class="bg-gradient-to-r from-brand-500 to-emerald-400 h-full w-0 transition-all duration-500"></div>
                </div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Pending Updates</span>
                    <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
                </div>
                <div class="text-2xl font-bold text-amber-400" id="statPendingFresh">-</div>
                <div class="text-[10px] text-gray-500 mt-1">Action required by students</div>
            </div>

            <div class="bg-dark-card border border-dark-border rounded-2xl p-4 relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between text-gray-400 text-xs mb-1">
                    <span>Resit Candidates</span>
                    <i data-lucide="rotate-cw" class="w-4 h-4 text-purple-400"></i>
                </div>
                <div class="text-2xl font-bold text-purple-400" id="statTotalResit">-</div>
                <div class="text-[10px] text-gray-500 mt-1">Exempted from bio & photo upload</div>
            </div>
        </div>

        <!-- Filter & Actions Bar -->
        <div class="bg-dark-card border border-dark-border rounded-2xl p-4 mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 shadow-sm">
            
            <!-- Search Input -->
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="searchInput" placeholder="Search by your full name or exam/indexing number (e.g. B/130)..." class="w-full pl-10 pr-4 py-2 bg-dark-bg border border-dark-border rounded-xl text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loader" class="py-16 flex flex-col items-center justify-center text-gray-400 gap-3 hidden">
            <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-brand-500"></i>
            <p class="text-xs">Loading graduand profile records...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-16 flex flex-col items-center justify-center text-gray-500 gap-3 hidden">
            <div class="w-12 h-12 rounded-full bg-gray-800/50 flex items-center justify-center text-gray-600">
                <i data-lucide="search-x" class="w-6 h-6"></i>
            </div>
            <p class="text-sm font-medium text-gray-400">No matching student records found.</p>
            <p class="text-xs">Try searching by your exam number or clearing your filters.</p>
        </div>

        <!-- MOBILE VIEW: Interactive Card Grid (< 768px) -->
        <div id="mobileGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:hidden"></div>

        <!-- DESKTOP VIEW: Data Table (>= 768px) -->
        <div id="desktopTableWrapper" class="hidden md:block bg-dark-card border border-dark-border rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-dark-bg/60 border-b border-dark-border text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            <th class="py-3 px-4 w-12 text-center">No.</th>
                            <th class="py-3 px-4">Full Name</th>
                            <th class="py-3 px-4">Exam / Index Number</th>
                            <th class="py-3 px-4">Cadre</th>
                            <th class="py-3 px-4">Papers</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Status & Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-dark-border text-xs font-normal"></tbody>
                </table>
            </div>
        </div>

        <!-- Footer Hidden Export Button -->
        <div class="mt-12 mb-4 flex justify-end px-4">
            <button type="button" id="btnExportCsv" class="text-[10px] text-gray-600 opacity-20 hover:opacity-100 hover:text-emerald-400 transition-all flex items-center gap-1 cursor-pointer" title="Export Data">
                <i data-lucide="download" class="w-3 h-3"></i> export
            </button>
        </div>

    </main>

    <!-- Interactive Update & Cropper Modal -->
    <div id="updateModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden overflow-y-auto p-4 flex items-center justify-center">
        <div class="bg-dark-card border border-dark-border rounded-3xl w-full max-w-2xl overflow-hidden relative shadow-2xl my-8 animate-fade-in">
            <button type="button" id="btnCloseModal" class="absolute top-5 right-5 text-gray-400 hover:text-white transition z-10 bg-dark-bg/80 rounded-full p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <!-- Modal Header -->
            <div class="bg-dark-bg/80 border-b border-dark-border px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400 shrink-0">
                        <i data-lucide="camera" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span id="modalStudentName">Student Name</span>
                            <span id="modalTypeBadge" class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase">Fresh</span>
                        </h2>
                        <p class="text-xs font-mono text-brand-400 mt-0.5" id="modalStudentExam">B/130/000/23</p>
                    </div>
                </div>
            </div>

            <!-- Modal Form -->
            <form id="profileUpdateForm" class="p-6 space-y-6">
                <input type="hidden" id="modalStudentId" name="id">

                <!-- Previous Submission Banner if editing -->
                <div id="editBanner" class="hidden bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-3.5 flex items-center gap-3 text-xs text-emerald-300">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                    <div>
                        <p class="font-semibold">Record Previously Completed</p>
                        <p class="text-[11px] text-emerald-300/80">You can update your date of birth, blood group, or upload and crop a new passport photograph below.</p>
                    </div>
                </div>

                <!-- Bio Data Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="dobInput" class="block text-xs font-semibold text-gray-300 mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-brand-400"></i>
                            Date of Birth <span class="text-red-400">*</span>
                        </label>
                        <input type="date" id="dobInput" name="dob" required class="w-full px-3.5 py-2.5 bg-dark-bg border border-dark-border rounded-xl text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition">
                    </div>

                    <div>
                        <label for="bloodGroupInput" class="block text-xs font-semibold text-gray-300 mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="heart" class="w-3.5 h-3.5 text-red-400"></i>
                            Blood Group <span class="text-red-400">*</span>
                        </label>
                        <select id="bloodGroupInput" name="blood_group" required class="w-full px-3.5 py-2.5 bg-dark-bg border border-dark-border rounded-xl text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition cursor-pointer">
                            <option value="" disabled selected>-- Select Blood Group --</option>
                            <option value="A+">A+ (A Positive)</option>
                            <option value="A-">A- (A Negative)</option>
                            <option value="B+">B+ (B Positive)</option>
                            <option value="B-">B- (B Negative)</option>
                            <option value="AB+">AB+ (AB Positive)</option>
                            <option value="AB-">AB- (AB Negative)</option>
                            <option value="O+">O+ (O Positive)</option>
                            <option value="O-">O- (O Negative)</option>
                        </select>
                    </div>
                </div>

                <!-- Current Photo Display / Upload Trigger -->
                <div id="photoSection" class="border-t border-dark-border pt-5">
                    <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp" class="hidden">
                    
                    <label class="block text-xs font-semibold text-gray-300 mb-2 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="image" class="w-3.5 h-3.5 text-brand-400"></i>
                            Passport Photograph (Square / Passport Ratio) <span class="text-red-400">*</span>
                        </span>
                        <span class="text-[10px] text-gray-400 font-normal">Saved as: <strong id="savedPhotoName" class="font-mono text-brand-400">B.130.12.22.jpg</strong></span>
                    </label>

                    <!-- Existing Photo Preview Card if present -->
                    <div id="currentPhotoWrapper" class="hidden mb-4 bg-dark-bg/60 border border-dark-border rounded-2xl p-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img id="currentPhotoImg" src="" alt="Current Passport" class="w-16 h-16 rounded-xl object-cover border border-brand-500/40 shadow-md">
                            <div>
                                <p class="text-xs font-semibold text-white">Current Saved Photograph</p>
                                <p class="text-[10px] text-gray-400">Click below if you wish to crop and replace this photo</p>
                            </div>
                        </div>
                        <button type="button" id="btnTriggerReplacePhoto" class="px-3.5 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-medium border border-dark-border transition flex items-center gap-1.5">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-brand-400"></i> Change Photo
                        </button>
                    </div>

                    <!-- Photo Upload Dropzone -->
                    <div id="photoDropZone" class="border-2 border-dashed border-dark-border hover:border-brand-500/60 rounded-2xl p-6 text-center cursor-pointer transition bg-dark-bg/40 group">
                        <i data-lucide="upload-cloud" class="w-10 h-10 text-brand-400 mx-auto mb-2 group-hover:scale-110 transition"></i>
                        <p class="text-xs font-semibold text-gray-200">Click anywhere here or drag & drop image to select passport</p>
                        <p class="text-[11px] text-gray-400 mt-1">Accepts JPG, PNG, WEBP files. You will adjust & crop to passport square next.</p>
                    </div>

                    <!-- Cropper Canvas Container -->
                    <div id="cropperWrapper" class="hidden mt-4 space-y-3">
                        <div class="relative bg-dark-bg border border-dark-border rounded-2xl overflow-hidden max-h-[380px] flex items-center justify-center p-2 min-h-[220px]">
                            <img id="imageToCrop" src="" alt="Crop Preview" class="max-w-full max-h-[360px] block">
                        </div>

                        <!-- Cropper Action Controls -->
                        <div class="flex flex-wrap items-center justify-between gap-2 bg-dark-bg border border-dark-border rounded-xl p-2.5">
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="btn-crop-ctrl px-2.5 py-1.5 bg-brand-500 text-white rounded-lg text-xs font-semibold shadow transition flex items-center gap-1" data-action="mode-crop" id="btnModeCrop" title="Draw/Select Area on Image">
                                    <i data-lucide="crop" class="w-3.5 h-3.5"></i> Draw Area
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2.5 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition flex items-center gap-1" data-action="mode-move" id="btnModeMove" title="Move/Pan Image">
                                    <i data-lucide="move" class="w-3.5 h-3.5 text-brand-400"></i> Pan Image
                                </button>
                                <span class="h-4 w-px bg-dark-border mx-0.5 hidden sm:inline-block"></span>
                                <button type="button" class="btn-crop-ctrl px-2 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition flex items-center gap-1" data-action="zoom-in" title="Zoom In">
                                    <i data-lucide="zoom-in" class="w-3.5 h-3.5 text-brand-400"></i> +
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition flex items-center gap-1" data-action="zoom-out" title="Zoom Out">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5 text-brand-400"></i> -
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition flex items-center gap-1" data-action="rotate-left" title="Rotate Left">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-brand-400"></i>
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition flex items-center gap-1" data-action="rotate-right" title="Rotate Right">
                                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-brand-400"></i>
                                </button>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button type="button" class="btn-crop-ctrl px-2.5 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition" data-action="ratio-square" title="1:1 Passport Square">
                                    1:1 Square
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2.5 py-1.5 bg-dark-card hover:bg-gray-800 rounded-lg text-xs font-medium text-gray-300 border border-dark-border transition" data-action="ratio-passport" title="3:4 Standard Passport">
                                    3:4 Passport
                                </button>
                                <button type="button" class="btn-crop-ctrl px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-medium border border-red-500/20 transition" data-action="reset" title="Reset Crop">
                                    Reset
                                </button>
                                <button type="button" id="btnChangePhotoInner" class="px-3 py-1.5 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 rounded-lg text-xs font-medium border border-brand-500/20 transition flex items-center gap-1">
                                    <i data-lucide="folder-open" class="w-3.5 h-3.5"></i> Change Photo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission Loader -->
                <div id="modalSubmitLoader" class="hidden py-2 flex items-center justify-center gap-2 text-brand-400 text-xs">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    <span>Saving profile and cropped passport photograph...</span>
                </div>

                <!-- Modal Actions -->
                <div class="border-t border-dark-border pt-4 flex items-center justify-end gap-3">
                    <button type="button" id="btnCancelModal" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-medium transition">
                        Cancel
                    </button>
                    <button type="submit" id="btnSaveProfile" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold shadow-lg shadow-emerald-500/25 active:scale-95 transition flex items-center gap-2 text-xs">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Save Profile & Passport
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-16 opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-2.5 bg-dark-card border border-brand-500/30 text-white px-4 py-3 rounded-xl shadow-2xl max-w-sm">
        <i data-lucide="check-circle" id="toastIcon" class="w-4 h-4 text-brand-500 shrink-0"></i>
        <span id="toastMsg" class="text-xs font-medium">Saved</span>
    </div>

    <!-- App JavaScript Logic -->
    <script>
        $(document).ready(function() {
            let currentGraduands = [];
            let searchTimeout = null;
            let cropperInstance = null;
            let hasNewPhotoChosen = false;

            // Initialize Lucide Icons
            lucide.createIcons();

            // Load records initial
            fetchGraduands();

            // Search input with debounce
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchGraduands, 250);
            });

            // Filter dropdown changes
            $('#filterType, #filterStatus, #filterCadre').on('change', fetchGraduands);

            // Export Excel / CSV with currently active filters
            $('#btnExportCsv').on('click', function() {
                const search = encodeURIComponent($('#searchInput').val() || '');
                const typeFilter = encodeURIComponent($('#filterType').val() || 'all');
                const status = encodeURIComponent($('#filterStatus').val() || 'all');
                const cadre = encodeURIComponent($('#filterCadre').val() || '');

                const exportUrl = `graduand_api.php?action=export_csv&search=${search}&type_filter=${typeFilter}&status=${status}&cadre=${cadre}`;
                showToast('Generating Excel / CSV export file...');
                window.location.href = exportUrl;
            });

            // Fetch records from API
            function fetchGraduands() {
                $('#loader').removeClass('hidden');
                $('#emptyState').addClass('hidden');

                const params = {
                    action: 'get_graduands',
                    search: $('#searchInput').val(),
                    type_filter: $('#filterType').val(),
                    status: $('#filterStatus').val(),
                    cadre: $('#filterCadre').val()
                };

                $.getJSON('graduand_api.php', params, function(res) {
                    $('#loader').addClass('hidden');
                    if (!res.success) {
                        showToast('Error loading records: ' + res.error, false);
                        return;
                    }

                    currentGraduands = res.graduands;
                    updateStats(res.stats);
                    populateCadreDropdown(res.filters);
                    renderRecords();
                }).fail(function() {
                    $('#loader').addClass('hidden');
                    showToast('Network error connecting to graduand API.', false);
                });
            }

            // Populate Cadre filter once
            let cadresPopulated = false;
            function populateCadreDropdown(filters) {
                if (cadresPopulated || !filters.cadres) return;
                cadresPopulated = true;
                const currCadre = $('#filterCadre').val();
                filters.cadres.forEach(c => {
                    $('#filterCadre').append(`<option value="${c}">${c}</option>`);
                });
                if (currCadre) $('#filterCadre').val(currCadre);
            }

            // Update stats cards
            function updateStats(stats) {
                $('#statTotalFresh').text(stats.total_fresh);
                $('#statCompletedFresh').text(stats.completed_fresh);
                $('#statPendingFresh').text(stats.pending_fresh);
                $('#statTotalResit').text(stats.total_resit);

                const percent = stats.total_fresh > 0 ? Math.round((stats.completed_fresh / stats.total_fresh) * 100) : 0;
                $('#statProgressBar').css('width', percent + '%');
            }

            // Render mobile cards & desktop rows
            function renderRecords() {
                const mobGrid = $('#mobileGrid');
                const tbBody = $('#tableBody');
                mobGrid.empty();
                tbBody.empty();

                if (currentGraduands.length === 0) {
                    $('#emptyState').removeClass('hidden');
                    return;
                }

                currentGraduands.forEach((stu, idx) => {
                    const isFresh = (stu.type || '').toLowerCase() === 'fresh';
                    const hasCompleted = stu.photo && stu.photo !== '' && stu.dob && stu.dob !== '';
                    
                    let statusBadge = '';
                    let cardBorder = 'bg-dark-card border-dark-border';
                    let rowHover = 'hover:bg-dark-border/40';

                    if (!isFresh) {
                        statusBadge = `<span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20"><i data-lucide="slash" class="w-3 h-3"></i> Resit (Exempted)</span>`;
                        rowHover = 'opacity-75 hover:opacity-100 transition';
                    } else if (hasCompleted) {
                        statusBadge = `
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">
                                <i data-lucide="check-circle" class="w-3 h-3"></i> Completed
                            </span>`;
                        cardBorder = 'bg-brand-500/5 border-brand-500/30';
                    } else {
                        statusBadge = `
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i> Pending Update
                            </span>`;
                    }

                    const typeBadge = isFresh ? 
                        '<span class="text-[10px] font-bold px-2 py-0.5 rounded border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 uppercase">Fresh</span>' : 
                        '<span class="text-[10px] font-bold px-2 py-0.5 rounded border bg-purple-500/10 text-purple-400 border-purple-500/20 uppercase">Resit</span>';

                    // Thumbnail check
                    let thumbHtml = '';
                    if (stu.photo && stu.photo !== '') {
                        thumbHtml = `<img src="${stu.photo}?t=${Date.now()}" alt="thumb" class="w-9 h-9 rounded-lg object-cover border border-brand-500/40 shrink-0 shadow-sm">`;
                    } else {
                        thumbHtml = `
                            <div class="w-9 h-9 rounded-lg bg-gray-800 border border-dark-border flex items-center justify-center text-gray-500 shrink-0">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>`;
                    }

                    // Mobile Card
                    const cardHtml = `
                        <div class="student-item cursor-pointer rounded-2xl border p-4 transition-all duration-200 active:scale-[0.98] relative ${cardBorder}" data-id="${stu.id}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    ${thumbHtml}
                                    <div>
                                        <h3 class="text-sm font-semibold text-white tracking-tight">${stu.full_name || 'Unnamed Candidate'}</h3>
                                        <p class="text-xs font-mono text-brand-400 mt-0.5">${stu.exam_number || '-'}</p>
                                    </div>
                                </div>
                                ${typeBadge}
                            </div>
                            <div class="mt-3 pt-3 border-t border-dark-border/60 flex items-center justify-between gap-2 text-[11px] text-gray-400">
                                <span><strong class="text-gray-300">Cadre:</strong> ${stu.cadre || '-'}</span>
                                ${statusBadge}
                            </div>
                        </div>
                    `;
                    mobGrid.append(cardHtml);

                    // Desktop Row
                    const rowHtml = `
                        <tr class="student-item cursor-pointer transition ${rowHover}" data-id="${stu.id}">
                            <td class="py-3 px-4 text-center font-mono text-gray-400">${idx + 1}</td>
                            <td class="py-3 px-4 text-white font-medium flex items-center gap-3">
                                ${thumbHtml}
                                <span>${stu.full_name || '-'}</span>
                            </td>
                            <td class="py-3 px-4 font-mono text-brand-400 font-semibold">${stu.exam_number || '-'}</td>
                            <td class="py-3 px-4">${stu.cadre || '-'}</td>
                            <td class="py-3 px-4 text-gray-400 text-xs">${stu.papers || '-'}</td>
                            <td class="py-3 px-4">${typeBadge}</td>
                            <td class="py-3 px-4">${statusBadge}</td>
                        </tr>
                    `;
                    tbBody.append(rowHtml);
                });

                lucide.createIcons();
            }

            // Student item click -> Open Update Modal
            $(document).on('click', '.student-item', function() {
                const id = $(this).data('id');
                const stu = currentGraduands.find(s => s.id == id);
                if (!stu) return;

                // Check eligibility
                if ((stu.type || '').toLowerCase() !== 'fresh') {
                    showToast("Only Fresh graduand candidates are required or eligible to upload passport and biodata.", false);
                    return;
                }

                openUpdateModal(stu);
            });

            // Open Modal & Populate data
            function openUpdateModal(stu) {
                $('#modalStudentId').val(stu.id);
                $('#modalStudentName').text(stu.full_name || 'Student Name');
                $('#modalStudentExam').text(stu.exam_number || '-');

                // Compute expected saved filename
                let cleanExam = (stu.exam_number || '').replace(/[\/\\\s]/g, '.').replace(/[^A-Za-z0-9\.-]/g, '');
                cleanExam = cleanExam.replace(/\.\.+/g, '.').replace(/^\.+|\.+$/g, '');
                if (!cleanExam) cleanExam = 'student_' + stu.id;
                $('#savedPhotoName').text(cleanExam + '.jpg');

                // Populate existing values if editing
                $('#dobInput').val(stu.dob || '');
                $('#bloodGroupInput').val(stu.blood_group || '');

                hasNewPhotoChosen = false;
                destroyCropper();

                if (stu.photo && stu.photo !== '') {
                    $('#editBanner').removeClass('hidden');
                    $('#currentPhotoWrapper').removeClass('hidden');
                    $('#currentPhotoImg').attr('src', stu.photo + '?t=' + Date.now());
                    $('#photoDropZone').addClass('hidden');
                } else {
                    $('#editBanner').addClass('hidden');
                    $('#currentPhotoWrapper').addClass('hidden');
                    $('#photoDropZone').removeClass('hidden');
                }

                $('#updateModal').removeClass('hidden');
            }

            // Close Modal
            $('#btnCloseModal, #btnCancelModal').on('click', function() {
                $('#updateModal').addClass('hidden');
                destroyCropper();
            });

            // Trigger replace or change photo
            $('#btnTriggerReplacePhoto, #btnChangePhotoInner').on('click', function(e) {
                e.preventDefault();
                $('#photoFileInput').click();
            });

            // Photo dropzone click & drag drop
            $('#photoDropZone').on('click', function(e) {
                if ($(e.target).is('#photoFileInput')) return;
                $('#photoFileInput').click();
            });

            const dropZoneEl = document.getElementById('photoDropZone');
            if (dropZoneEl) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZoneEl.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $('#photoDropZone').addClass('border-brand-500 bg-brand-500/10');
                    }, false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZoneEl.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $('#photoDropZone').removeClass('border-brand-500 bg-brand-500/10');
                    }, false);
                });
                dropZoneEl.addEventListener('drop', function(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files && files.length > 0) {
                        handlePhotoFile(files[0]);
                    }
                }, false);
            }

            // File selection via input
            $('#photoFileInput').on('change', function() {
                const file = this.files && this.files[0];
                if (file) {
                    handlePhotoFile(file);
                }
            });

            function handlePhotoFile(file) {
                if (!file || !file.type.match('image.*')) {
                    showToast('Please select a valid image file (JPG, PNG, WEBP).', false);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#photoDropZone').addClass('hidden');
                    $('#cropperWrapper').removeClass('hidden');
                    hasNewPhotoChosen = true;

                    const $img = $('#imageToCrop');
                    destroyCropper();
                    
                    $img.one('load', function() {
                        initCropper();
                    }).attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }

            // Initialize Cropper.js instance
            function initCropper() {
                if (cropperInstance) {
                    cropperInstance.destroy();
                }
                const imageElement = document.getElementById('imageToCrop');
                cropperInstance = new Cropper(imageElement, {
                    aspectRatio: 1, // Strictly enforce 1:1 Square Passport by default
                    viewMode: 1,
                    dragMode: 'move', // Default to move/pan so clicking outside the box never erases the initial square box!
                    autoCrop: true,
                    autoCropArea: 0.7,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: true,
                    ready: function() {
                        if (cropperInstance) {
                            // Automatically and explicitly render a clean 1:1 centered square selection box immediately upon load!
                            const containerData = cropperInstance.getContainerData();
                            const size = Math.min(containerData.width, containerData.height) * 0.72;
                            if (size > 0) {
                                cropperInstance.setCropBoxData({
                                    width: size,
                                    height: size,
                                    left: (containerData.width - size) / 2,
                                    top: (containerData.height - size) / 2
                                });
                            }
                            // Strictly re-lock the aspect ratio to 1:1 square after setting initial box dimensions
                            cropperInstance.setAspectRatio(1);
                        }
                        // Set active button UI to Pan/Resize Mode
                        $('#btnModeMove').removeClass('bg-dark-card text-gray-300').addClass('bg-brand-500 text-white font-semibold shadow');
                        $('#btnModeCrop').removeClass('bg-brand-500 text-white font-semibold shadow').addClass('bg-dark-card text-gray-300');
                    }
                });
            }

            // Destroy Cropper instance
            function destroyCropper() {
                if (cropperInstance) {
                    cropperInstance.destroy();
                    cropperInstance = null;
                }
                $('#cropperWrapper').addClass('hidden');
                $('#imageToCrop').attr('src', '');
                $('#photoFileInput').val('');
            }

            // Cropper control buttons
            $(document).on('click', '.btn-crop-ctrl', function() {
                if (!cropperInstance) return;
                const action = $(this).data('action');

                switch (action) {
                    case 'mode-crop':
                        cropperInstance.setDragMode('crop');
                        cropperInstance.setAspectRatio(1); // Ensure 1:1 square stays locked when drawing
                        $('#btnModeCrop').removeClass('bg-dark-card text-gray-300').addClass('bg-brand-500 text-white font-semibold shadow');
                        $('#btnModeMove').removeClass('bg-brand-500 text-white font-semibold shadow').addClass('bg-dark-card text-gray-300');
                        showToast('Draw Selection Mode: Click and drag over your face on the picture.');
                        break;
                    case 'mode-move':
                        cropperInstance.setDragMode('move');
                        cropperInstance.setAspectRatio(1); // Ensure 1:1 square stays locked when moving
                        $('#btnModeMove').removeClass('bg-dark-card text-gray-300').addClass('bg-brand-500 text-white font-semibold shadow');
                        $('#btnModeCrop').removeClass('bg-brand-500 text-white font-semibold shadow').addClass('bg-dark-card text-gray-300');
                        showToast('Pan Mode: Click and drag to move the photograph.');
                        break;
                    case 'zoom-in':
                        cropperInstance.zoom(0.15);
                        break;
                    case 'zoom-out':
                        cropperInstance.zoom(-0.15);
                        break;
                    case 'rotate-left':
                        cropperInstance.rotate(-90);
                        break;
                    case 'rotate-right':
                        cropperInstance.rotate(90);
                        break;
                    case 'ratio-square':
                        cropperInstance.setAspectRatio(1);
                        showToast('Cropper set to 1:1 Square Ratio');
                        break;
                    case 'ratio-passport':
                        cropperInstance.setAspectRatio(3 / 4);
                        showToast('Cropper set to 3:4 Standard Passport Ratio');
                        break;
                    case 'reset':
                        cropperInstance.reset();
                        $('#btnModeCrop').click();
                        break;
                }
            });

            // Submit Profile Update Form
            $('#profileUpdateForm').on('submit', function(e) {
                e.preventDefault();

                const studentId = $('#modalStudentId').val();
                const dob = $('#dobInput').val();
                const bloodGroup = $('#bloodGroupInput').val();

                if (!dob || !bloodGroup) {
                    showToast('Please fill in both Date of Birth and Blood Group.', false);
                    return;
                }

                // Check if student has neither current photo nor new cropped photo
                const hasCurrentPhoto = !$('#currentPhotoWrapper').hasClass('hidden');
                if (!hasCurrentPhoto && !hasNewPhotoChosen) {
                    showToast('Please upload and crop your passport photograph.', false);
                    return;
                }

                let photoDataUrl = '';
                if (hasNewPhotoChosen && cropperInstance) {
                    const canvas = cropperInstance.getCroppedCanvas({
                        width: 600,
                        height: 600,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    });
                    if (!canvas) {
                        showToast('Could not generate cropped image canvas.', false);
                        return;
                    }
                    photoDataUrl = canvas.toDataURL('image/jpeg', 0.92);
                }

                const postData = {
                    action: 'update_profile',
                    id: studentId,
                    dob: dob,
                    blood_group: bloodGroup,
                    photo_data: photoDataUrl
                };

                $('#modalSubmitLoader').removeClass('hidden');
                $('#btnSaveProfile').prop('disabled', true);

                $.post('graduand_api.php', postData, function(res) {
                    $('#modalSubmitLoader').addClass('hidden');
                    $('#btnSaveProfile').prop('disabled', false);

                    if (res.success) {
                        showToast(res.message || 'Biodata & Passport updated successfully!');
                        $('#updateModal').addClass('hidden');
                        destroyCropper();
                        fetchGraduands(); // Refresh dashboard and cards
                    } else {
                        showToast(res.error || 'Failed to update record.', false);
                    }
                }).fail(function() {
                    $('#modalSubmitLoader').addClass('hidden');
                    $('#btnSaveProfile').prop('disabled', false);
                    showToast('Network error while saving profile.', false);
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
                }, 3000);
            }
        });
    </script>
</body>
</html>
