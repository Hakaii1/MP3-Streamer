// js/library.js

// --- PLAYLISTS ---
async function fetchPlaylists() { 
    try { 
        const res = await fetch('api_playlists.php'); 
        if (res.ok) { 
            playlists = await res.json(); 
            renderSidebarPlaylists(); 
        } 
    } catch(e){} 
}

function renderSidebarPlaylists() { 
    const container = document.getElementById('playlistSidebarList'); 
    const names = Object.keys(playlists); 
    if (names.length === 0) container.innerHTML = '<div class="text-gray-600 text-xs italic px-1">No playlists yet</div>'; 
    else container.innerHTML = names.map(name => `<button onclick="showLibrary('${escapeHtml(name)}')" class="w-full text-left text-gray-400 hover:text-white text-sm py-1 truncate px-1 rounded hover:bg-white/5"><i class="fa-solid fa-list-ul mr-2 text-xs"></i> ${escapeHtml(name)}</button>`).join(''); 
}

function showLibrary(playlistName) { 
    document.getElementById('tab-library').classList.remove('hidden'); 
    document.getElementById('tab-devices').classList.add('hidden'); 
    document.getElementById('tab-alarm').classList.add('hidden'); 
    document.getElementById('deleteBanner').classList.add('hidden'); 
    
    toggleGradient(true);

    document.getElementById('searchInput').value = "";
    searchQuery = "";

    if (playlistName === 'all') { 
        currentViewTracks = [...allTracks]; 
        currentPlaylistName = null; 
        document.getElementById('viewTitle').innerText = "All Songs"; 
        document.getElementById('deletePlaylistBtn').classList.add('hidden');
        document.getElementById('addExistingBtn').classList.add('hidden');
        document.getElementById('headerIcon').className = "fa-solid fa-heart"; 
    } else { 
        let key = playlistName; 
        if (!playlists[key]) key = Object.keys(playlists).find(k => k === playlistName) || playlistName; 
        if (playlists[key]) { 
            currentViewTracks = playlists[key]; 
            currentPlaylistName = key; 
            document.getElementById('viewTitle').innerText = key; 
            document.getElementById('deletePlaylistBtn').classList.remove('hidden'); 
            document.getElementById('addExistingBtn').classList.remove('hidden');
            document.getElementById('headerIcon').className = "fa-solid fa-compact-disc"; 
        } 
    } 
    renderSongList(); 
}

// --- RENDER SONG LIST ---
function renderSongList() {
    const container = document.getElementById('songListContainer'); 
    
    const filteredItems = currentViewTracks
        .map((track, index) => ({ track, index }))
        .filter(item => item.track.toLowerCase().includes(searchQuery));
        
    document.getElementById('songCount').innerText = `${filteredItems.length} tracks found`;
    
    if (filteredItems.length === 0) {
            container.innerHTML = `<div class="p-8 text-center text-gray-500">No tracks found matching "${escapeHtml(searchQuery)}"</div>`;
            return;
    }

    container.innerHTML = filteredItems.map((item) => {
        const rawFile = item.track; 
        const safeFile = escapeHtml(rawFile); 
        const displayName = safeFile.replace(/\.mp3$/i, ''); 
        
        const deleteTitle = currentPlaylistName ? "Remove from Playlist" : "Delete File";
        const deleteIcon = currentPlaylistName ? "fa-minus-circle" : "fa-trash";
        
        let dur = durationCache[rawFile];
        if (!dur) {
            dur = '--:--';
            queueDurationFetch(rawFile);
        }
        
        return `
        <div onclick="playIndex(${item.index})" class="group grid grid-cols-[50px_1fr_65px_50px] gap-2 px-4 py-3 rounded-md hover:bg-white/10 transition cursor-pointer items-center">
            <div class="text-center text-gray-500 font-mono text-sm group-hover:hidden">${item.index + 1}</div>
            <div class="text-center hidden group-hover:block text-pink-custom"><i class="fa-solid fa-play"></i></div>
            <div class="text-white font-medium truncate min-w-0">${displayName}</div>
            <div class="text-center text-xs text-gray-500 font-mono" id="dur-${escapeId(rawFile)}">${dur}</div>
            <div class="flex justify-end">
                <button onclick="event.stopPropagation(); deleteTrack('${safeFile}')" class="text-gray-500 hover:text-red-500 opacity-0 group-hover:opacity-100 transition p-2" title="${deleteTitle}">
                    <i class="fa-solid ${deleteIcon}"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function handleSearch(val) {
    searchQuery = val.trim().toLowerCase();
    renderSongList();
}

// --- FILE UPLOAD ---
function triggerUpload() {
    document.getElementById('ajaxFileInput').value = '';
    document.getElementById('ajaxFileInput').click();
}

async function handleFileSelect(input) {
    if (input.files.length === 0) return;
    const file = input.files[0];
    const fd = new FormData();
    fd.append('file', file);
    if (currentPlaylistName) {
        fd.append('target_playlist', currentPlaylistName);
    }

    const btn = document.querySelector('button[onclick="triggerUpload()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
    btn.disabled = true;

    try {
        const res = await fetch('api_upload.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.ok) {
            if (currentPlaylistName) {
                await fetchPlaylists();
                showLibrary(currentPlaylistName);
            } else {
                location.reload(); 
            }
        } else {
            alert('Upload Failed: ' + data.error);
        }
    } catch (e) {
        alert('Upload error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// --- DELETE ---
function deleteTrack(filename) { 
    const isPlaylistView = !!currentPlaylistName;
    const msg = isPlaylistView 
        ? `Remove "${filename}" from this playlist? (File will remain in storage)`
        : `PERMANENTLY DELETE "${filename}" from disk?`;
        
    if (confirm(msg)) { 
        if (isPlaylistView) {
            fetch('api_playlists.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'remove_track',
                    name: currentPlaylistName,
                    track: filename
                })
            }).then(async () => {
                await fetchPlaylists();
                showLibrary(currentPlaylistName);
            });
        } else {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('file', filename);
            fetch('api_files.php', { method: 'POST', body: fd })
                .then(() => location.reload());
        }
    } 
}

// --- MODALS (Create/Add) ---
function openCreatePlaylistModal() { 
    document.getElementById('createPlaylistModal').classList.remove('hidden'); 
    document.getElementById('createPlaylistModal').classList.add('flex'); 
    document.getElementById('newPlaylistName').value = ""; 
    document.getElementById('newPlaylistName').focus(); 
    const list = document.getElementById('newPlaylistSongs'); 
    list.innerHTML = allTracks.map(f => { 
        const safe = escapeHtml(f); 
        const display = safe.replace(/\.mp3$/i, ''); 
        return `<label class="flex items-center gap-3 p-2 hover:bg-[#333] rounded cursor-pointer"><input type="checkbox" class="custom-checkbox new-playlist-check" value="${safe}"> <span class="text-sm text-gray-300">${display}</span></label>`; 
    }).join(''); 
}

function closeCreatePlaylistModal() { 
    document.getElementById('createPlaylistModal').classList.add('hidden'); 
    document.getElementById('createPlaylistModal').classList.remove('flex'); 
}

async function saveNewPlaylist() { 
    const name = document.getElementById('newPlaylistName').value.trim(); 
    if (!name) return alert("Please enter a playlist name"); 
    const checks = document.querySelectorAll('.new-playlist-check:checked'); 
    const songs = Array.from(checks).map(c => c.value); 
    if (songs.length === 0) return alert("Please select at least one song"); 
    await fetch('api_playlists.php', { method: 'POST', body: JSON.stringify({action:'save', name, songs}) }); 
    closeCreatePlaylistModal(); 
    fetchPlaylists(); 
    setTimeout(() => showLibrary(name), 500); 
}

function openAddExistingModal() {
    if (!currentPlaylistName) return;
    const modal = document.getElementById('addExistingModal');
    document.getElementById('addToPlaylistName').innerText = currentPlaylistName;
    
    const existingInPlaylist = playlists[currentPlaylistName] || [];
    const available = allTracks.filter(t => !existingInPlaylist.includes(t));
    
    const list = document.getElementById('existingSongsList');
    if (available.length === 0) {
        list.innerHTML = '<div class="text-gray-500 text-sm p-4">All available songs are already in this playlist.</div>';
    } else {
        list.innerHTML = available.map(f => {
            const safe = escapeHtml(f);
            const display = safe.replace(/\.mp3$/i, '');
            return `<label class="flex items-center gap-3 p-2 hover:bg-[#333] rounded cursor-pointer border-b border-[#222]">
                <input type="checkbox" class="custom-checkbox add-existing-check" value="${safe}"> 
                <span class="text-sm text-gray-300">${display}</span>
            </label>`;
        }).join('');
    }
    
    modal.classList.remove('hidden'); modal.classList.add('flex');
}

function closeAddExistingModal() {
    document.getElementById('addExistingModal').classList.add('hidden');
    document.getElementById('addExistingModal').classList.remove('flex');
}

async function saveAddedSongs() {
    if (!currentPlaylistName) return;
    const checks = document.querySelectorAll('.add-existing-check:checked');
    const songs = Array.from(checks).map(c => c.value);
    
    if (songs.length === 0) { closeAddExistingModal(); return; }
    
    const btn = document.querySelector('button[onclick="saveAddedSongs()"]');
    btn.innerText = "Adding...";
    
    await fetch('api_playlists.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'add_tracks',
            name: currentPlaylistName,
            songs: songs
        })
    });
    
    btn.innerText = "Add Selected";
    closeAddExistingModal();
    await fetchPlaylists();
    showLibrary(currentPlaylistName);
}

// --- DELETE PLAYLIST LOGIC ---
function triggerDeleteBanner() { 
    document.getElementById('deleteBanner').classList.remove('hidden'); 
    document.getElementById('deleteBannerName').innerText = currentPlaylistName; 
}
function hideDeleteBanner() { 
    document.getElementById('deleteBanner').classList.add('hidden'); 
}
async function confirmDelete() { 
    await fetch('api_playlists.php', { method: 'POST', body: JSON.stringify({action:'delete', name: currentPlaylistName}) }); 
    hideDeleteBanner(); 
    fetchPlaylists(); 
    showLibrary('all'); 
}