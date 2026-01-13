// ALARM FETCHING, RENDERING, AND LOGIC

async function fetchAlarms() { 
    try { 
        const res = await fetch('api_alarms.php?ts=' + Date.now()); 
        alarmsData = await res.json(); 
        renderAlarms(); 
    } catch(e){} 
}

function renderAlarms() { 
    const list = document.getElementById('alarmList'); 
    const slots = (alarmsData.slots || []).slice().sort((a, b) => (a.time || '').localeCompare(b.time || '')); 
    
    if (slots.length === 0) { 
        list.innerHTML = `<div class="text-gray-500 text-sm italic px-2">No alarms set.</div>`; 
        return; 
    } 

    const now = new Date();
    const curMins = now.getHours() * 60 + now.getMinutes();
    let nextAlarmId = null;
    let minDiff = Infinity;

    slots.forEach(s => {
        if(!s.enabled) return;
        const [h, m] = s.time.split(':').map(Number);
        let alarmMins = h * 60 + m;
        let diff = alarmMins - curMins;
        if (diff <= 0) diff += 1440; 
        if (diff < minDiff) {
            minDiff = diff;
            nextAlarmId = s.id;
        }
    });

    list.innerHTML = slots.map(s => { 
        const id = escapeHtml(s.id); 
        const enabled = !!s.enabled; 
        const dispTime = escapeHtml(to12HourDisplay(s.time)); 
        
        const isNext = (s.id === nextAlarmId);
        const borderClass = isNext ? 'border-pink-custom' : 'border-[#222]';
        const bgClass = isNext ? 'bg-[#1a1a1a]' : 'bg-[#111]';
        const badge = isNext ? `<span class="bg-pink-custom text-black text-[10px] font-bold px-2 py-0.5 rounded-full ml-3 animate-pulse">UP NEXT</span>` : '';
        const shadowStyle = isNext ? 'box-shadow: 0 0 15px rgba(248,200,220,0.2);' : '';

        return `<div class="${bgClass} border ${borderClass} rounded-lg px-4 py-3 flex items-center justify-between gap-4 transition-all duration-300" style="${shadowStyle}">
            <div class="w-[140px] font-mono text-sm text-white flex items-center">
                ${dispTime}
                ${badge}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-white font-bold truncate">${escapeHtml(s.title)}</div>
                <div class="text-xs text-gray-400 truncate">${escapeHtml(s.file)}</div>
            </div>
            <div class="flex items-center gap-3 justify-end w-[160px]">
                <button onclick="toggleAlarm('${id}')" class="${enabled ? 'text-pink-custom' : 'text-gray-500'}" title="Toggle"><i class="fa-solid fa-toggle-${enabled ? 'on' : 'off'}"></i></button>
                <button onclick="deleteAlarm('${id}')" class="text-red-400 hover:text-red-300" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>`; 
    }).join(''); 
}

async function saveAlarmSlot() { 
    const typed = document.getElementById('alarmTimeInput').value; 
    const ampm = document.getElementById('alarmAmPmSelect').value; 
    const time24 = parseTypedTimeTo24h(typed, ampm); 
    const title = document.getElementById('alarmTitleInput').value.trim(); 
    const file = document.getElementById('alarmFileSelect').value; 
    if (!time24 || !title || !file) return alert('Invalid Input'); 
    await fetch('api_alarms.php', { method:'POST', body: JSON.stringify({action:'create', time:time24, title, file}) }); 
    closeAlarmModal(); 
    fetchAlarms(); 
}

async function toggleAlarm(id) { 
    await fetch('api_alarms.php', { method:'POST', body: JSON.stringify({action:'toggle', id}) }); 
    fetchAlarms(); 
}

async function deleteAlarm(id) { 
    if(!confirm("Delete?")) return; 
    await fetch('api_alarms.php', { method:'POST', body: JSON.stringify({action:'delete', id}) }); 
    fetchAlarms(); 
}

function openAlarmModal() { 
    document.getElementById('alarmModal').classList.remove('hidden'); 
    document.getElementById('alarmModal').classList.add('flex'); 
}

function closeAlarmModal() { 
    document.getElementById('alarmModal').classList.add('hidden'); 
    document.getElementById('alarmModal').classList.remove('flex'); 
}