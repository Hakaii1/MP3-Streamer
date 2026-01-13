// MAIN JAVASCRIPT, HANDLES INITIALIZATION AND GLOBAL STATE

// Global State
let currentIndex = 0; 
let currentViewTracks = [...allTracks]; 
let isPlaying = false; 
let isShuffleOn = false; 
let playlists = {}; 
let currentPlaylistName = null; 
let selectedSongs = new Set(); 
let alarmsData = { slots: [], last_event: null };
let snapshotTime = 0; 
let wasPlayingBeforeAlarm = false; 
let lastAlarmId = null;
let serverStartTs = 0; 
let serverOffset = 0; 
let searchQuery = "";
let isSeeking = false; 
let ignoreSyncUntil = 0; 

// Duration Cache
let durationCache = {}; 
let durationQueue = []; 
let isProcessingQueue = false;

// Audio Objects
const audio = new Audio(); 
audio.muted = true; 
audio.preload = "metadata";
const alarmAudio = new Audio();

audio.addEventListener('ended', playNext);

function init() {
    renderSongList(); 
    fetchAlarms(); 
    fetchPlaylists(); 
    setInterval(fetchState, 1000); 
    setInterval(backgroundTicker, 100);
    
    const fileSel = document.getElementById('alarmFileSelect'); 
    if (chimeTracks.length > 0) fileSel.innerHTML = chimeTracks.map(f => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join(''); 
    else fileSel.innerHTML = `<option value="">(No files found)</option>`;
    
    toggleGradient(true);
}

// Queue Helper
function queueDurationFetch(filename) {
    if (durationCache[filename] || durationQueue.includes(filename)) return;
    durationQueue.push(filename);
    processDurationQueue();
}

function processDurationQueue() {
    if (isProcessingQueue || durationQueue.length === 0) return;
    isProcessingQueue = true;

    const filename = durationQueue.shift();
    const tempAudio = new Audio();
    tempAudio.src = 'stream.php?file=' + encodeURIComponent(filename);
    
    tempAudio.onloadedmetadata = () => {
        const dur = tempAudio.duration;
        if (dur && !isNaN(dur)) {
            durationCache[filename] = formatTime(dur);
            const el = document.getElementById(`dur-${escapeId(filename)}`);
            if (el) el.innerText = durationCache[filename];
        }
        cleanup();
    };
    
    tempAudio.onerror = cleanup;

    function cleanup() {
        tempAudio.remove();
        isProcessingQueue = false;
        setTimeout(processDurationQueue, 50);
    }
}

function escapeId(str) {
    return str.replace(/[^a-zA-Z0-9]/g, '_');
}

// State Sync
async function fetchState() { 
    if (isSeeking) return; 
    try { 
        const reqStart = Date.now();
        const res = await fetch('api_state.php?_=' + reqStart); 
        if (!res.ok) return; 
        const data = await res.json(); 
        if(!data || !data.global) return; 
        
        const now = Date.now();
        const latency = (now - reqStart) / 1000 / 2;
        serverOffset = (now/1000) - (data.server_time + latency);

        handleAlarmEvent(data.alarm_event); 
        document.getElementById('sidebarDeviceCount').innerText = Object.keys(data.clients || {}).length; 
        renderClients(data.clients || {}); 

        if (Date.now() < ignoreSyncUntil) return; 

        const g = data.global;
        const serverTrack = g.track || "";
        
        if (serverTrack && (document.getElementById('barTrackName').innerText !== serverTrack.replace(/\.mp3$/i, ''))) {
            document.getElementById('barTrackName').innerText = serverTrack.replace(/\.mp3$/i, '');
            const idx = currentViewTracks.indexOf(serverTrack);
            if (idx !== -1) currentIndex = idx;
            
            if (!audio.src.includes(encodeURIComponent(serverTrack))) {
                    audio.src = `stream.php?file=${encodeURIComponent(serverTrack)}`;
            }
        }
        
        serverStartTs = g.start_ts;
        if (g.status === 'playing') {
            isPlaying = true;
            updatePlayIcon();
            if (audio.paused && Math.abs(audio.currentTime - ((data.server_time - g.start_ts))) > 2) {
                audio.currentTime = Math.max(0, data.server_time - g.start_ts);
                audio.muted = true; audio.play().catch(e=>{});
            }
        } else {
            isPlaying = false; updatePlayIcon(); audio.pause();
        }
    } catch (e) {} 
}

function handleAlarmEvent(evt) {
    if (!evt || evt.id === lastAlarmId) return; 
    lastAlarmId = evt.id;
    wasPlayingBeforeAlarm = isPlaying; 
    snapshotTime = audio.currentTime; 
    sendAction('global', 'pause'); 
    audio.pause(); 
    isPlaying = false; 
    updatePlayIcon();
    document.getElementById('hostAlarmBanner').classList.remove('hidden'); 
    document.getElementById('hostAlarmBannerTitle').innerText = evt.title; 
    document.getElementById('hostAlarmBannerFile').innerText = evt.file;
    alarmAudio.src = `stream.php?file=${encodeURIComponent(evt.file)}&alarm=1`; 
    alarmAudio.play();
}

function finishAlarm() { 
    document.getElementById('hostAlarmBanner').classList.add('hidden'); 
    alarmAudio.pause(); 
    // The Client (client.php) handles resuming music. Host should just update UI.
    if (wasPlayingBeforeAlarm) {
        // Optional: Just unpause visually without forcing a track change
        isPlaying = true; 
        updatePlayIcon();
        sendAction('global', 'play'); // Simple resume command, NO track change
    }
}
alarmAudio.onended = finishAlarm; 
document.getElementById('hostAlarmBannerClose').onclick = finishAlarm;

function renderClients(clients) {
        const grid = document.getElementById('clientGrid'); 
        const ids = Object.keys(clients);
        if (ids.length === 0) { grid.innerHTML = '<div class="text-gray-500 col-span-3 text-center py-10">No devices connected.</div>'; return; }
        grid.innerHTML = ids.map(id => {
            const c = clients[id]; const isPlaying = c.status === 'playing'; const vol = Math.round(c.volume * 100); const safeTrack = escapeHtml(c.track || 'No Track').replace(/\.mp3$/i, ''); const diffVal = c.diff !== undefined ? c.diff.toFixed(3) : '--';
            return `<div class="bg-[#111] p-5 rounded-lg border border-[#222] hover:bg-[#1a1a1a] transition group relative"><div class="flex justify-between items-start mb-4"><div class="flex items-center gap-3"><div class="w-10 h-10 bg-pink-custom rounded-full flex items-center justify-center text-black font-bold shadow-lg"><i class="fa-solid fa-laptop"></i></div><div><div class="text-white font-bold text-sm truncate w-32">${escapeHtml(c.name)}</div><div class="text-xs ${isPlaying ? 'text-pink-custom' : 'text-gray-500'} font-bold uppercase tracking-wider">${isPlaying ? 'Playing' : c.status}</div></div></div><div class="text-right"><div class="text-[10px] text-gray-500 font-mono">OFFSET</div><div class="text-xs font-mono ${Math.abs(c.diff) > 0.1 ? 'text-red-400' : 'text-green-400'}">${diffVal}s</div></div></div><div class="text-xs text-gray-400 mb-4 truncate"><i class="fa-solid fa-music mr-1"></i> ${safeTrack}</div><div class="flex items-center justify-between border-t border-[#333] pt-4 gap-2"><div class="flex gap-2 flex-shrink-0"><button onclick="sendAction('${id}', 'play')" class="text-white hover-text-pink"><i class="fa-solid fa-play"></i></button><button onclick="sendAction('${id}', 'pause')" class="text-white hover-text-pink"><i class="fa-solid fa-pause"></i></button><button onclick="sendAction('${id}', 'restart')" class="text-white hover-text-pink" title="Reload Client"><i class="fa-solid fa-rotate-right"></i></button></div><div class="flex items-center gap-2 flex-1 min-w-0 justify-end"><i class="fa-solid fa-volume-low text-xs text-pink-custom flex-shrink-0"></i><input type="range" min="0" max="100" value="${vol}" class="flex-1 w-full min-w-[50px] max-w-[100px] accent-pink-custom cursor-pointer" oninput="sendAction('${id}', 'volume', this.value/100)"></div></div></div>`;
        }).join('');
}

function showTab(tab) { 
    document.getElementById('tab-library').classList.add('hidden'); 
    document.getElementById('tab-devices').classList.add('hidden'); 
    document.getElementById('tab-alarm').classList.add('hidden'); 
    toggleGradient(false);
    document.getElementById('tab-' + tab).classList.remove('hidden'); 
}

// Start everything
init();