// FOR AUDIO PLAYBACK, PROGRESS BAR, AND SERVER COMMANDS

async function playIndex(idx, startAt = 0) {
    document.getElementById('progressBar').value = 0;
    updateProgressVisual(document.getElementById('progressBar'));
    document.getElementById('currentTime').innerText = "0:00";

    if (idx < 0) idx = currentViewTracks.length - 1;
    if (idx >= currentViewTracks.length) idx = 0;
    currentIndex = idx;
    const trackName = currentViewTracks[currentIndex];
    document.getElementById('barTrackName').innerText = trackName.replace(/\.mp3$/i, '');
    
    if (!audio.src.includes(encodeURIComponent(trackName))) {
        audio.src = `stream.php?file=${encodeURIComponent(trackName)}`;
        audio.load();
    }
    
    audio.currentTime = startAt; 
    try { await audio.play(); } catch (e) {} 
    
    isPlaying = true; updatePlayIcon();
    
    ignoreSyncUntil = Date.now() + 1500;
    const now = (Date.now() / 1000) - serverOffset;
    serverStartTs = now - startAt; 

    const fd = new FormData(); 
    fd.append('action', 'play_at'); 
    fd.append('track', trackName);
    fd.append('seek', startAt); 
    fd.append('target', 'global');
    fd.append('queue', JSON.stringify(currentViewTracks));
    await fetch('api_state.php', { method: 'POST', body: fd });
}

function toggleGlobal() {
    if (isPlaying) { 
        audio.pause(); 
        isPlaying = false; 
        sendAction('global', 'pause'); 
    } else { 
        playIndex(currentIndex, audio.currentTime); 
    }
    updatePlayIcon();
}

function toggleShuffle() { 
    isShuffleOn = !isShuffleOn; 
    const btn = document.getElementById('shuffleBtn'); 
    const dot = document.getElementById('shuffleDot'); 
    if (isShuffleOn) { 
        btn.classList.remove('text-gray-400'); btn.classList.add('text-pink-custom'); dot.classList.remove('hidden'); 
    } else { 
        btn.classList.add('text-gray-400'); btn.classList.remove('text-pink-custom'); dot.classList.add('hidden'); 
    } 
}

function playNext() {
    if (isShuffleOn && currentViewTracks.length > 1) { 
        let nextIdx = Math.floor(Math.random() * currentViewTracks.length); 
        if (nextIdx === currentIndex) nextIdx = (nextIdx + 1) % currentViewTracks.length; 
        playIndex(nextIdx); 
    } else { 
        playIndex(currentIndex + 1); 
    }
}

function playPrev() { playIndex(currentIndex - 1); }

function seekGlobal(perc) { 
    const time = (audio.duration || 1) * (perc/100); 
    audio.currentTime = time; 
    document.getElementById('progressBar').blur();

    const now = (Date.now() / 1000) - serverOffset;
    serverStartTs = now - time;
    
    ignoreSyncUntil = Date.now() + 1500;
    isSeeking = false;
    isPlaying = true; 
    updatePlayIcon();

    const fd = new FormData(); 
    fd.append('action', 'play_at'); 
    fd.append('track', currentViewTracks[currentIndex]); 
    fd.append('seek', time); 
    fd.append('target', 'global');
    fd.append('queue', JSON.stringify(currentViewTracks));
    fetch('api_state.php', { method: 'POST', body: fd }); 
    
    backgroundTicker(); 
}

async function sendAction(target, action, extra = null) { 
    const fd = new FormData(); 
    fd.append('action', action); 
    fd.append('target', target); 
    if(action === 'volume') fd.append('volume', extra); 
    if(action === 'play') fd.append('queue', JSON.stringify(currentViewTracks)); 
    await fetch('api_state.php', { method: 'POST', body: fd }); 
}

function updatePlayIcon() { 
    document.getElementById('playIcon').className = isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play ml-1'; 
}

function handleDrag(perc) {
    updateProgressVisual(document.getElementById('progressBar'));
    const dur = audio.duration || 1;
    const time = dur * (perc/100);
    document.getElementById('currentTime').innerText = formatTime(time);
}

function updateProgressVisual(rangeInput) { 
    const val = rangeInput.value; 
    const max = rangeInput.max || 100; 
    const perc = (val / max) * 100; 
    rangeInput.style.backgroundSize = `${perc}% 100%`; 
}

function backgroundTicker() {
    if (!isPlaying || isSeeking) return; 
    
    let cur = audio.currentTime;
    if (audio.paused && serverStartTs > 0) {
        const now = (Date.now() / 1000) - serverOffset; 
        cur = Math.max(0, now - serverStartTs);
    }

    const dur = audio.duration || 1;
    
    if (dur > 1) {
        document.getElementById('currentTime').innerText = formatTime(cur); 
        document.getElementById('totalTime').innerText = formatTime(dur);
        const bar = document.getElementById('progressBar'); 
        if (document.activeElement !== bar) { 
            bar.value = (cur / dur) * 100; 
            updateProgressVisual(bar); 
        }
    }
}