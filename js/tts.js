// Global variable to store the current TTS data
let currentTTSData = {
    url: null,
    filename: null,
    text: null
};

async function convertTTS() {
    const textInput = document.getElementById('ttsInput');
    const statusDiv = document.getElementById('ttsStatus');
    const text = textInput.value.trim();

    if (!text) {
        statusDiv.innerHTML = '<span class="text-yellow-400"><i class="fa-solid fa-exclamation-circle mr-1"></i>Please enter text</span>';
        return;
    }

    statusDiv.innerHTML = '<span class="text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Generating...</span>';

    try {
        const response = await fetch('api_tts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: text })
        });

        const data = await response.json();

        if (data.success) {
            statusDiv.innerHTML = '<span class="text-green-400"><i class="fa-solid fa-check-circle mr-1"></i>Done</span>';

            // 1. Store Data
            currentTTSData.url = data.file_url;
            currentTTSData.text = text;
            // Extract filename from URL for scheduling (e.g., "tts/file.mp3")
            // Assuming data.file_url is like "http://localhost/tts/abc.mp3"
            const urlParts = data.file_url.split('/');
            currentTTSData.filename = urlParts[urlParts.length - 1];

            // 2. Populate Modal
            const modal = document.getElementById('ttsPreviewModal');
            const audio = document.getElementById('ttsPreviewAudio');
            const textDisplay = document.getElementById('ttsGeneratedText');

            audio.src = data.file_url;
            // Optional: Auto-play preview
            // audio.play().catch(e => console.log("Autoplay blocked")); 

            textDisplay.textContent = `"${text}"`;

            // 3. Show Modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');

        } else {
            statusDiv.innerHTML = `<span class="text-red-400">${data.error}</span>`;
        }
    } catch (error) {
        console.error(error);
        statusDiv.innerHTML = '<span class="text-red-400">Network Error</span>';
    }
}

// Option 1: Announce Now (Broadcast to all devices)
async function announceTTSNow() {
    if (!currentTTSData.url) return;

    // 1. Check if Chime is playing
    const currentTrack = document.getElementById('barTrackName')?.innerText || '';
    // Check against global chimeTracks if available, or assume files in chimes dir have specific pattern
    // Also check if the current track matches any known chime filename
    // Safer: check if currentTrack exists in chimeTracks array passed from PHP
    const isChime = typeof chimeTracks !== 'undefined' && chimeTracks.some(c => c.replace(/\.mp3$/i, '') === currentTrack);

    if (isChime) {
        alert("Cannot announce while a chime is playing.");
        return;
    }

    // 2. Trigger Immediate Alarm
    const trackName = "tts/" + currentTTSData.filename;

    try {
        const response = await fetch('api_alarms.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'trigger',
                title: "TTS: " + currentTTSData.text.substring(0, 15) + "...",
                file: trackName
            }),
            headers: { 'Content-Type': 'application/json' }
        });

        const res = await response.json();
        if (res.ok) {
            showToast('Announcement starting...', 'success');
            closeTTSModal();
        } else {
            alert("Failed to announce: " + (res.error || 'Unknown error'));
        }
    } catch (e) {
        console.error(e);
        alert("Failed to announce.");
    }
}

// Option 2: Schedule (Save as Alarm)
async function saveTTSSchedule() {
    const timeVal = document.getElementById('ttsScheduleTime').value;
    const ampm = document.getElementById('ttsScheduleAmPm').value;

    if (!timeVal) {
        alert("Please enter a time.");
        return;
    }

    // Convert to 24h for check
    const time24 = parseTypedTimeTo24h(timeVal, ampm);
    if (!time24) {
        alert("Invalid time format.");
        return;
    }

    // Check for conflicts
    if (typeof alarmsData !== 'undefined' && alarmsData.slots) {
        // Check if any ENABLED slot matches the time
        const conflict = alarmsData.slots.find(s => s.enabled && s.time === time24);
        if (conflict) {
            alert(`Cannot schedule: Conflict with existing alarm "${conflict.title}" at ${time24}.`);
            return;
        }
    }

    const alarmData = {
        action: 'create',
        time: time24,
        title: "TTS: " + currentTTSData.text.substring(0, 15) + "...",
        file: "tts/" + currentTTSData.filename
    };

    try {
        const response = await fetch('api_alarms.php', {
            method: 'POST',
            body: JSON.stringify(alarmData),
            headers: { 'Content-Type': 'application/json' }
        });

        const res = await response.json();

        if (res.ok) {
            showToast(`Scheduled for ${timeVal} ${ampm}`, 'success');
            closeTTSModal();
            // Refresh alarms list if function exists
            if (typeof fetchAlarms === 'function') {
                await fetchAlarms();
            }
            // Update the display
            renderScheduledTTS();
        } else {
            alert("Failed to schedule: " + (res.error || 'Unknown error'));
        }

    } catch (e) {
        console.error(e);
        alert("Failed to schedule.");
    }
}

// Render the scheduled TTS list
function renderScheduledTTS() {
    const list = document.getElementById('scheduledTTSList');
    if (!list) return;

    if (typeof alarmsData === 'undefined' || !alarmsData.slots) {
        list.innerHTML = '<div class="text-gray-500 text-sm text-center italic">Loading...</div>';
        return;
    }

    // Filter only TTS alarms (starting with tts/)
    const ttsSlots = alarmsData.slots.filter(s => s.file && s.file.startsWith('tts/'))
        .sort((a, b) => (a.time || '').localeCompare(b.time || ''));

    if (ttsSlots.length === 0) {
        list.innerHTML = '<div class="text-gray-500 text-sm text-center italic">No scheduled announcements.</div>';
        return;
    }

    list.innerHTML = ttsSlots.map(s => {
        const id = escapeHtml(s.id);
        const timeDisp = escapeHtml(to12HourDisplay(s.time));
        const title = escapeHtml(s.title);

        return `
        <div class="bg-[#111] border border-[#333] rounded px-3 py-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-pink-custom text-black text-xs font-bold px-2 py-0.5 rounded">${timeDisp}</div>
                <div class="text-white text-sm truncate max-w-[200px]" title="${title}">${title}</div>
            </div>
            <button onclick="deleteScheduledTTS('${id}')" class="text-gray-500 hover:text-red-400 transition" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`;
    }).join('');
}

// Wrapper to delete TTS alarm (re-uses existing API)
async function deleteScheduledTTS(id) {
    if (!confirm("Remove this scheduled announcement?")) return;
    try {
        await fetch('api_alarms.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });

        // Refresh data
        if (typeof fetchAlarms === 'function') {
            await fetchAlarms();
        }
        renderScheduledTTS();
    } catch (e) {
        console.error(e);
        alert("Failed to delete.");
    }
}

// Hook into existing alarm refresh if possible
// We can override the existing renderAlarms to also update our list
// But let's check if we can add a listener or just rely on manual calls.
// Best approach: Modifying alarms.js to call this would be ideal, but here we can just update it when we know data changed 
// or periodically if app.js polls.
// For now, we assume fetchAlarms updates the global alarmsData, so we can poll or hook.

// We can monkey-patch fetchAlarms if it's global? 
// Or just rely on the user interacting.
// Let's hook into the global window object if possible.
if (typeof window.originalRenderAlarms === 'undefined' && typeof renderAlarms === 'function') {
    window.originalRenderAlarms = renderAlarms;
    renderAlarms = function () {
        window.originalRenderAlarms();
        renderScheduledTTS();
    };
} else {
    // Fallback: Try to render initially
    setTimeout(renderScheduledTTS, 1000);
}