<?php
// host.php
// Modularized Version

$musicDir = __DIR__ . '/mp3';
$files = [];
if (is_dir($musicDir)) {
    if ($dh = opendir($musicDir)) {
        while (($file = readdir($dh)) !== false) { if (preg_match('/\.mp3$/i', $file)) $files[] = $file; }
        closedir($dh);
    }
}
sort($files);

$chimesDir = __DIR__ . '/chimes'; $chimes = [];
if (is_dir($chimesDir)) {
    if ($dh = opendir($chimesDir)) {
        while (($file = readdir($dh)) !== false) { if (preg_match('/\.(mp3|wav|ogg|m4a)$/i', $file)) $chimes[] = $file; }
        closedir($dh);
    }
}
sort($chimes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <title>MP3 Host</title> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="icon" type="image/x-icon" href="images/logo.jpg">
    <style>
        :root { --pink: #F8C8DC; }
        .text-pink-custom { color: var(--pink); } .bg-pink-custom { background-color: var(--pink); }
        .hover-text-pink:hover { color: var(--pink); } .accent-pink-custom { accent-color: var(--pink); }
        .custom-checkbox { appearance: none; background-color: #333; width: 1.25rem; height: 1.25rem; border-radius: 0.25rem; display: inline-grid; place-content: center; cursor: pointer; }
        .custom-checkbox::before { content: ""; width: 0.65em; height: 0.65em; transform: scale(0); transition: 120ms transform ease-in-out; box-shadow: inset 1em 1em var(--pink); transform-origin: center; clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%); }
        .custom-checkbox:checked::before { transform: scale(1); } .custom-checkbox:checked { background-color: #333; }
        .progress-range { -webkit-appearance: none; height: 4px; border-radius: 2px; background: #333; background-image: linear-gradient(var(--pink), var(--pink)); background-repeat: no-repeat; background-size: 0% 100%; }
        .progress-range::-webkit-slider-thumb { -webkit-appearance: none; height: 12px; width: 12px; border-radius: 50%; background: white; cursor: pointer; box-shadow: 0 0 2px 0 #555; transition: background .3s ease-in-out; opacity: 0; }
        .group:hover .progress-range::-webkit-slider-thumb { opacity: 1; }
        .modal-scroll::-webkit-scrollbar { width: 8px; } .modal-scroll::-webkit-scrollbar-track { background: #111; } .modal-scroll::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        
        .custom-scroll { scrollbar-width: thin; scrollbar-color: transparent transparent; }
        .custom-scroll:hover { scrollbar-color: #555 transparent; }
        .custom-scroll::-webkit-scrollbar { width: 8px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: transparent; border-radius: 10px; border: 2px solid transparent; background-clip: content-box; }
        .custom-scroll:hover::-webkit-scrollbar-thumb { background-color: #555; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #777; }
    </style>
</head>
<body class="bg-[#121212] text-white h-screen flex flex-col overflow-hidden selection:bg-pink-custom selection:text-black">
    
    <input type="file" id="ajaxFileInput" accept=".mp3" class="hidden" onchange="handleFileSelect(this)">
    
    <div id="hostAlarmBanner" class="hidden fixed top-0 left-0 right-0 z-50 bg-[#111] border-b border-pink-custom shadow-pink h-24 flex items-center">
        <div class="w-full max-w-7xl mx-auto px-6 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-pink-custom text-black flex items-center justify-center flex-shrink-0 animate-pulse"><i class="fa-solid fa-bell text-xl"></i></div>
            <div class="text-left min-w-0 flex-1">
                <div id="hostAlarmBannerTitle" class="text-white font-bold text-xl truncate">Alarm</div>
                <div id="hostAlarmBannerFile" class="text-sm text-gray-400 truncate"></div>
            </div>
            <button id="hostAlarmBannerClose" class="ml-auto text-gray-400 hover:text-white transition p-2"><i class="fa-solid fa-xmark text-2xl"></i></button>
        </div>
    </div>

    <header class="flex-shrink-0 h-16 bg-black flex items-center justify-between px-8 z-40 relative p-5">
        <div class="flex items-center gap-4 w-[320px] flex-shrink-0">
            <img src="images/logo.jpg" alt="logo" class="w-13 h-11 object-contain">
            <div class="text-pink-custom text-2xl font-bold tracking-tight leading-[0.9]">
                LA ROSE NOIRE
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center gap-6">
            <div class="w-full max-w-xl relative group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-pink-custom transition-colors">
                    <i class="fa-solid fa-search"></i>
                </div>
                <input type="text" id="searchInput" placeholder="Search..." class="w-full bg-[#222] border border-transparent rounded-full py-2.5 pl-12 pr-4 text-sm text-white focus:outline-none focus:bg-[#333] focus:border-pink-custom transition-all placeholder-gray-500 shadow-inner" oninput="handleSearch(this.value)">
            </div>
        </div>
        <div class="w-[320px] flex-shrink-0 hidden lg:block"></div>
    </header>

    <div class="flex flex-1 overflow-hidden relative gap-3 p-3 bg-black pb-28">
        
        <div class="w-[350px] bg-[#121212] flex-shrink-0 flex flex-col p-6 gap-6 hidden md:flex rounded-[15px] overflow-hidden shadow-lg border border-[#222]">
            <nav class="space-y-4 pt-2">
                <button onclick="showLibrary('all')" class="nav-btn w-full text-left flex items-center gap-4 hover-text-pink transition font-bold text-gray-300"><i class="fa-solid fa-music text-xl w-6"></i> All Songs</button>
                <button onclick="showTab('devices')" class="nav-btn w-full text-left flex items-center gap-4 hover-text-pink transition font-bold text-gray-300"><i class="fa-solid fa-server text-xl w-6"></i> Devices <span id="sidebarDeviceCount" class="ml-auto bg-[#333] text-xs text-white px-2 py-0.5 rounded-full">0</span></button>
                <button onclick="showTab('alarm')" class="nav-btn w-full text-left flex items-center gap-4 hover-text-pink transition font-bold text-gray-300"><i class="fa-solid fa-clock text-xl w-6"></i> Chimes</button>
            </nav>
            <div class="mt-4 pt-4 border-t border-[#222] flex-1 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between mb-4"><h3 class="text-xs text-gray-500 font-bold uppercase tracking-widest">Playlists</h3><button onclick="openCreatePlaylistModal()" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-plus"></i></button></div>
                <div id="playlistSidebarList" class="space-y-2 overflow-y-auto modal-scroll pr-2"></div>
            </div>
            <div class="mt-auto pt-4 border-t border-[#222] flex justify-center items-center pb-2">
                <img src="images/logo_it.png" alt="IT Logo" class="h-20 w-auto object-contain opacity-90 hover:opacity-100 transition mt-4">
            </div>
        </div>

        <div id="mainContentPanel" class="flex-1 bg-[#111] overflow-y-auto relative rounded-[15px] p-8 shadow-lg border border-[#222] custom-scroll">
            <div id="tab-library">
                <div id="deleteBanner" class="hidden w-full bg-gradient-to-r from-red-900/90 to-black border border-red-900 p-4 rounded-lg mb-6 flex items-center justify-between shadow-red-900/20 shadow-xl transition-all">
                    <div class="flex items-center gap-4"><div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white text-lg"><i class="fa-solid fa-triangle-exclamation"></i></div><div><h4 class="text-white font-bold text-lg">Delete Playlist?</h4><p class="text-red-200 text-sm">Deleting "<span id="deleteBannerName" class="font-bold"></span>".</p></div></div>
                    <div class="flex items-center gap-3"><button onclick="hideDeleteBanner()" class="px-4 py-2 text-sm font-bold text-gray-300 hover:text-white transition">Cancel</button><button onclick="confirmDelete()" class="px-5 py-2 text-sm bg-red-600 hover:bg-red-500 text-white font-bold rounded-full shadow-lg transition">Yes, Delete</button></div>
                </div>
                <div class="flex items-end gap-6 mb-6">
                    <div class="w-48 h-48 bg-gradient-to-br from-pink-300 to-pink-600 shadow-2xl flex items-center justify-center text-6xl text-black font-bold rounded-md"><i id="headerIcon" class="fa-solid fa-heart"></i></div>
                    <div class="flex-1">
                        <p class="text-xs uppercase font-bold tracking-wider text-pink-custom">Library</p><h1 id="viewTitle" class="text-5xl font-black text-white mb-2">All Songs</h1><p id="songCount" class="text-sm text-gray-400 mb-4">Loading...</p>
                        <div class="flex items-center gap-3 h-10">
                            <button onclick="triggerUpload()" class="bg-white text-black px-5 py-2 rounded-full text-sm font-bold flex items-center gap-2 hover:scale-105 transition shadow-lg"><i class="fa-solid fa-upload"></i> Upload</button>
                            <button id="addExistingBtn" onclick="openAddExistingModal()" class="hidden bg-[#333] text-white px-5 py-2 rounded-full text-sm font-bold flex items-center gap-2 hover:bg-[#444] transition shadow-lg"><i class="fa-solid fa-list-check"></i> Add Existing</button>
                            <button id="deletePlaylistBtn" class="hidden bg-white text-red-600 px-5 py-2 rounded-full font-bold text-sm flex items-center gap-2 hover:bg-gray-200 transition shadow-lg" onclick="triggerDeleteBanner()"><i class="fa-solid fa-trash"></i> Delete Playlist</button>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-[50px_1fr_65px_50px] gap-2 px-4 py-2 border-b border-[#333] text-sm uppercase text-gray-500 mb-2">
                    <span class="text-center">#</span> 
                    <span>Title</span> 
                    <span class="text-center"><i class="far fa-clock"></i></span>
                    <span class="text-right"></span>
                </div>
                
                <div id="songListContainer" class="flex flex-col gap-1"></div>
            </div>

            <div id="tab-devices" class="hidden"><h2 class="text-2xl font-bold text-white mb-6">Device Management</h2><div id="clientGrid" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6"></div></div>
            <div id="tab-alarm" class="hidden"><div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold text-white">Chimes</h2><button onclick="openAlarmModal()" class="bg-white text-black px-5 py-2 rounded-full text-sm font-bold flex items-center gap-2 hover:scale-105 transition shadow-lg"><i class="fa-solid fa-plus"></i> Add Timeslot</button></div><div class="grid grid-cols-[140px_1fr_160px] gap-4 px-4 py-2 border-b border-[#333] text-sm uppercase text-gray-500 mb-2"><span>Time</span> <span>Title</span> <span class="text-right">Actions</span></div><div id="alarmList" class="flex flex-col gap-2"></div></div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 h-24 bg-[#111] border-t border-[#222] flex flex-col justify-center px-6 z-50 group">
        <div class="flex items-center gap-2 w-full max-w-4xl mx-auto -mt-3 mb-1">
            <span id="currentTime" class="text-xs text-gray-400 w-10 text-right font-mono">0:00</span>
            <input type="range" id="progressBar" min="0" max="100" value="0" step="0.1" 
                class="progress-range flex-1 cursor-pointer"
                onmousedown="isSeeking=true"
                ontouchstart="isSeeking=true"
                oninput="handleDrag(this.value)" 
                onchange="seekGlobal(this.value)">
            <span id="totalTime" class="text-xs text-gray-400 w-10 font-mono">0:00</span>
        </div>
        <div class="flex items-center justify-between w-full">
            <div class="w-1/3 flex items-center gap-4"><div class="w-14 h-14 bg-[#222] flex items-center justify-center text-pink-custom rounded shadow-lg"><i class="fa-solid fa-music text-xl"></i></div><div class="overflow-hidden"><div id="barTrackName" class="text-white font-medium text-sm truncate">No Track Selected</div><div class="text-xs text-gray-400">Global Playback</div></div></div>
            <div class="w-1/3 flex flex-col items-center gap-2"><div class="flex items-center gap-6"><button id="shuffleBtn" onclick="toggleShuffle()" class="text-gray-400 hover:text-white transition relative"><i class="fa-solid fa-shuffle"></i><span id="shuffleDot" class="hidden absolute -bottom-2 left-1/2 -translate-x-1/2 w-1 h-1 bg-pink-custom rounded-full"></span></button><button onclick="playPrev()" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-backward-step text-xl"></i></button><button id="mainPlayBtn" onclick="toggleGlobal()" class="w-10 h-10 bg-white rounded-full text-black flex items-center justify-center hover:scale-105 transition shadow-lg"><i id="playIcon" class="fa-solid fa-play ml-1"></i></button><button onclick="playNext()" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-forward-step text-xl"></i></button><button onclick="sendAction('global', 'stop')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-stop"></i></button></div></div>
            <div class="w-1/3 flex justify-end items-center gap-3 group"><i class="fa-solid fa-volume-high text-pink-custom"></i><input type="range" min="0" max="100" value="100" class="w-24 accent-pink-custom cursor-pointer" oninput="sendAction('global', 'volume', this.value/100)"></div>
        </div>
    </div>

    <div id="createPlaylistModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[60]">
        <div class="bg-[#222] p-6 rounded-lg border border-pink-custom w-[500px] shadow-pink max-h-[80vh] flex flex-col">
            <h3 class="text-xl font-bold mb-4 text-white">Create New Playlist</h3>
            <input type="text" id="newPlaylistName" placeholder="Playlist Name" class="w-full bg-[#111] border border-[#333] p-3 rounded text-white mb-4 focus:outline-none focus:border-pink-custom">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Select Tracks</p><div id="newPlaylistSongs" class="flex-1 overflow-y-auto modal-scroll border border-[#333] rounded p-2 mb-4 bg-black/50"></div>
            <div class="flex justify-end gap-2 pt-2 border-t border-[#333]"><button onclick="closeCreatePlaylistModal()" class="px-4 py-2 text-gray-400 hover:text-white">Cancel</button><button onclick="saveNewPlaylist()" class="px-6 py-2 bg-pink-custom text-black font-bold rounded hover:scale-105 transition">Create</button></div>
        </div>
    </div>
    
    <div id="addExistingModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[60]">
        <div class="bg-[#222] p-6 rounded-lg border border-pink-custom w-[500px] shadow-pink max-h-[80vh] flex flex-col">
            <h3 class="text-xl font-bold mb-1 text-white">Add Songs</h3>
            <p class="text-xs text-gray-400 mb-4">Adding to: <span id="addToPlaylistName" class="text-pink-custom font-bold"></span></p>
            <div id="existingSongsList" class="flex-1 overflow-y-auto modal-scroll border border-[#333] rounded p-2 mb-4 bg-black/50"></div>
            <div class="flex justify-end gap-2 pt-2 border-t border-[#333]"><button onclick="closeAddExistingModal()" class="px-4 py-2 text-gray-400 hover:text-white">Cancel</button><button onclick="saveAddedSongs()" class="px-6 py-2 bg-pink-custom text-black font-bold rounded hover:scale-105 transition">Add Selected</button></div>
        </div>
    </div>

    <div id="alarmModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[60]"><div class="bg-[#222] p-6 rounded-lg border border-pink-custom w-96 shadow-pink"><h3 class="text-xl font-bold mb-4 text-pink-custom">Create Timeslot</h3><label class="text-xs text-gray-400 uppercase tracking-widest">Time</label><div class="flex gap-2 mb-4"><input type="text" id="alarmTimeInput" placeholder="07:30" class="flex-1 bg-[#111] border border-pink-custom p-2 rounded text-pink-custom focus:outline-none focus:border-pink-custom" /><select id="alarmAmPmSelect" class="w-28 bg-[#111] border border-pink-custom p-2 rounded text-pink-custom focus:outline-none focus:border-pink-custom"><option value="AM">AM</option> <option value="PM">PM</option></select></div><label class="text-xs text-gray-400 uppercase tracking-widest">Title</label><input type="text" id="alarmTitleInput" placeholder="Wake up" class="w-full bg-[#111] border border-[#333] p-2 rounded text-white mb-4 focus:outline-none focus:border-pink-custom"><label class="text-xs text-gray-400 uppercase tracking-widest">Audio (from .chimes)</label><select id="alarmFileSelect" class="w-full bg-[#111] border border-[#333] p-2 rounded text-white mb-6 focus:outline-none focus:border-pink-custom"></select><div class="flex justify-end gap-2"><button onclick="closeAlarmModal()" class="px-4 py-2 text-gray-400 hover:text-white">Cancel</button><button onclick="saveAlarmSlot()" class="px-4 py-2 bg-pink-custom text-black font-bold rounded hover:scale-105 transition">Save</button></div></div></div>

    <script>
        // Pass PHP variables to JS
        const allTracks = <?php echo json_encode($files); ?>; 
        const chimeTracks = <?php echo json_encode($chimes); ?>;
    </script>

    <script src="js/utils.js"></script>
    <script src="js/player.js"></script>
    <script src="js/library.js"></script>
    <script src="js/alarms.js"></script>
    <script src="js/app.js"></script>
</body>
</html>