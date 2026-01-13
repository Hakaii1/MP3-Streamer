// FOR FORMATTING AND TEXT SAFETY

function escapeHtml(text) {
    if (!text) return text;
    return text.replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;");
}

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return "0:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function to12HourDisplay(time24) {
    if (!time24) return '';
    let [hh, mm] = time24.split(':').map(x => parseInt(x, 10));
    const ampm = (hh >= 12) ? 'PM' : 'AM';
    let h12 = hh % 12;
    if (h12 === 0) h12 = 12;
    return String(h12).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ' ' + ampm;
}

function parseTypedTimeTo24h(typed, ampm) {
    const m = String(typed || '').trim().match(/^(\d{1,2}):(\d{2})$/);
    if (!m) return null;
    let hh = parseInt(m[1], 10);
    let mm = parseInt(m[2], 10);
    if (!(hh >= 1 && hh <= 12) || !(mm >= 0 && mm <= 59)) return null;
    if (ampm === 'AM' && hh === 12) hh = 0;
    if (ampm === 'PM' && hh !== 12) hh += 12;
    return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
}

// Toggle background gradient on the main panel
function toggleGradient(show) {
    const panel = document.getElementById('mainContentPanel');
    const gradientClass = 'bg-[linear-gradient(to_bottom,rgba(248,200,220,0.6)_0%,#111_315px)]';
    
    if (show) {
        panel.classList.add(gradientClass, 'bg-local', 'bg-no-repeat');
    } else {
        panel.classList.remove(gradientClass, 'bg-local', 'bg-no-repeat');
        panel.classList.remove('bg-[linear-gradient(to_bottom,rgba(248,200,220,0.2)_0%,#111_350px)]');
    }
}