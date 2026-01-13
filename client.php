<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>La Rose Client</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="icon" type="image/x-icon" href="images/logo.png">
  <link rel="stylesheet" href="styles/styles.css">
  <style>
    :root {
      --pink: #F8C8DC;
    }

    .text-pink-custom {
      color: var(--pink);
    }

    .bg-pink-custom {
      background-color: var(--pink);
    }

    .shadow-pink {
      box-shadow: 0 0 20px rgba(248, 200, 220, 0.4);
    }
  </style>
</head>

<body class="bg-black flex flex-col items-center justify-center h-screen text-center p-4">

  <div id="loginScreen" class="w-full max-w-sm bg-[#111] p-8 rounded-xl border border-[#222] shadow-2xl">
    <div class="mb-6 text-pink-custom text-4xl"><i class="fa-solid fa-headphones"></i></div>
    <h2 class="text-2xl font-bold text-white mb-2">Connect Device</h2>
    <p class="text-gray-500 text-sm mb-6">Enter a name for this speaker</p>

    <input type="text" id="deviceNameInput" placeholder="Example: Canteen Speaker"
      class="w-full bg-black border border-[#333] rounded px-4 py-3 text-white mb-4 focus:outline-none focus:border-pink-custom transition text-center" />

    <button onclick="startClient()" class="w-full bg-pink-custom text-black font-bold py-3 rounded hover:scale-105 transition shadow-pink">
      Connect
    </button>
  </div>

  <div id="mainInterface" class="hidden w-full max-w-md">
    <div id="alarmBanner" class="hidden fixed top-0 left-0 right-0 z-50 bg-[#111] border-b border-pink-custom shadow-pink">
      <div class="max-w-md mx-auto px-4 py-3 flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-pink-custom text-black flex items-center justify-center flex-shrink-0 animate-bounce">
          <i class="fa-solid fa-bell"></i>
        </div>
        <div class="text-left min-w-0">
          <div id="alarmBannerTitle" class="text-white font-bold truncate">Alarm</div>
          <div id="alarmBannerFile" class="text-xs text-gray-400 truncate"></div>
        </div>
        <button id="alarmBannerClose" class="ml-auto text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>

    <div class="w-full bg-[#111] rounded-xl p-8 shadow-2xl border border-[#222]">
      <h1 id="displayDeviceName" class="text-3xl font-bold text-pink-custom mb-2">Speaker</h1>
      <p class="text-gray-500 mb-6 text-xs uppercase tracking-widest">Connected</p>
      <!-- <div id="statusIndicator" class="w-16 h-16 rounded-full bg-yellow-600 mx-auto mb-6 flex items-center justify-center transition-all duration-500">
        <i id="statusIcon" class="fa-solid fa-link text-black text-xl"></i>
      </div> -->
      <img id="discImage" src="images/disc.png" alt="La Rose Logo" class="record-spin record-image h-24 w-24 mx-auto mb-6">
      <div class="mt-4 text-left space-y-2 border-t border-[#222] pt-4">
        <div class="flex justify-between items-end">
          <p class="text-xs text-gray-500 uppercase tracking-widest">Now Playing</p>
        </div>
        <p id="nowPlaying" class="text-lg font-medium truncate text-white">Connecting...</p>
      </div>
    </div>  
  </div>

  <audio id="audioPlayer" class="hidden" playsinline preload="auto"></audio>
  <audio id="alarmPlayer" class="hidden" playsinline preload="auto"></audio>

  <script>
    // --- NEW: Capture Boot Time for Remote Restart ---
    const bootTime = Date.now() / 1000;
    // ------------------------------------------------

    let clientId = localStorage.getItem('lanify_client_id');
    if (!clientId) {
      clientId = 'dev_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem('lanify_client_id', clientId);
    }

    let deviceName = localStorage.getItem('lanify_device_name') || '';
    document.getElementById('deviceNameInput').value = deviceName;

    const audio = document.getElementById('audioPlayer');
    const alarmAudio = document.getElementById('alarmPlayer');
    const nowPlaying = document.getElementById('nowPlaying');
    // const statusInd = document.getElementById('statusIndicator');
    const statusIcon = document.getElementById('statusIcon');
    const discImage = document.getElementById('discImage');
    const alarmBanner = document.getElementById('alarmBanner');

    let audioUnlocked = false;
    let currentTrack = null;
    let isMuted = false;
    let alarmInProgress = false;
    let lastAlarmEventId = localStorage.getItem('lanify_last_alarm_event') || null;
    let musicWasPlayingBeforeAlarm = false;
    let ignoreSyncUntil = 0;
    let lastCalculatedDiff = 0;

    async function startClient() {
      const inputName = document.getElementById('deviceNameInput').value.trim();
      if (!inputName) return alert("Please enter a device name.");

      deviceName = inputName;
      localStorage.setItem('lanify_device_name', deviceName);

      document.getElementById('loginScreen').classList.add('hidden');
      document.getElementById('mainInterface').classList.remove('hidden');
      document.getElementById('displayDeviceName').innerText = deviceName;

      // Unlock Audio
      if (!audioUnlocked) {
        audio.src = "data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA";
        try {
          await audio.play();
          audioUnlocked = true;
        } catch (e) {
          console.warn("Unlock failed", e);
        }
      }

      // --- SENSOR LOGIC ---
      // When song ends naturally, tell Server to skip to next
      audio.onended = () => {
        if (currentTrack) {
          console.log("Track finished. Reporting to Server...");
          const fd = new FormData();
          fd.append('action', 'track_finished');
          fd.append('target', 'global');
          fd.append('track', currentTrack);
          fetch('api_state.php', {
            method: 'POST',
            body: fd
          });
        }
      };

      poll();
    }

    alarmAudio.onerror = (e) => {
      alarmInProgress = false;
      resumeMusic();
    };
    alarmAudio.onended = () => {
      alarmInProgress = false;
      alarmBanner.classList.add('hidden');
      if (currentTrack) nowPlaying.innerText = currentTrack.replace(/\.mp3$/i, '');
      resumeMusic();
    };

    async function resumeMusic() {
      if (musicWasPlayingBeforeAlarm) {
        const fd = new FormData();
        fd.append('action', 'play');
        fd.append('target', 'global');
        await fetch('api_state.php', {
          method: 'POST',
          body: fd
        }).catch(console.error);
      }
    }

    async function runAlarm(evt) {
      if (!evt || !evt.id || evt.id === lastAlarmEventId || alarmInProgress) return;
      lastAlarmEventId = evt.id;
      localStorage.setItem('lanify_last_alarm_event', lastAlarmEventId);
      alarmInProgress = true;
      document.getElementById('alarmBannerTitle').innerText = evt.title || 'Alarm';
      document.getElementById('alarmBannerFile').innerText = evt.file || '';
      alarmBanner.classList.remove('hidden');
      musicWasPlayingBeforeAlarm = (!audio.paused && audio.currentTime > 0 && !audio.ended);
      try {
        audio.pause();
      } catch (e) {}
      alarmAudio.src = `stream.php?file=${encodeURIComponent(evt.file)}&alarm=1`;
      alarmAudio.volume = 1.0;
      alarmAudio.load();
      try {
        await alarmAudio.play();
      } catch (e) {}
    }

    document.getElementById('alarmBannerClose').addEventListener('click', () => {
      alarmBanner.classList.add('hidden');
      alarmAudio.pause();
      alarmInProgress = false;
      if (currentTrack) nowPlaying.innerText = currentTrack.replace(/\.mp3$/i, '');
      resumeMusic();
    });


    async function poll() {
      try {
        const reqStart = Date.now();
        const diffParam = lastCalculatedDiff.toFixed(4);
        const res = await fetch(`api_state.php?client_id=${clientId}&name=${encodeURIComponent(deviceName)}&diff=${diffParam}&_=${reqStart}`);
        if (!res.ok) throw new Error("API Error");
        const state = await res.json();
        const latency = (Date.now() - reqStart) / 1000 / 2;

        // --- NEW: CHECK FOR RESTART SIGNAL ---
        if (state.restart_ts && state.restart_ts > bootTime) {
          console.log("Restart signal received. Reloading...");
          location.reload();
          return;
        }
        // -------------------------------------

        runAlarm(state.alarm_event);

        discImage.classList.remove('hidden');

        // statusInd.classList.remove('bg-red-900', 'shadow-red'); // Clean up from error state
        // statusInd.classList.add('bg-pink-custom', 'shadow-pink'); // Apply success state (if needed, but already in HTML)
        // document.getElementById('statusIcon').className = "fa-solid fa-music text-black text-2xl";

        if (!alarmInProgress) {
          if (state.track && state.track !== currentTrack) {
            currentTrack = state.track;
            audio.src = 'stream.php?file=' + encodeURIComponent(state.track);
            nowPlaying.innerText = state.track.replace(/\.mp3$/i, '');
            ignoreSyncUntil = 0;
            if (state.status === 'playing') {
              const serverNow = state.server_time + latency;
              const startTs = state.start_ts || 0;
              const expectedTime = Math.max(0, serverNow - startTs);
              audio.currentTime = expectedTime;
              try {
                await audio.play();
              } catch (e) {}
            }
          }

          if (typeof state.volume === 'number' && Math.abs(audio.volume - state.volume) > 0.01) {
            audio.volume = state.volume;
          }

          if (state.status === 'playing') {
            const serverNow = state.server_time + latency;
            const startTs = state.start_ts || 0;
            const expectedTime = Math.max(0, serverNow - startTs);
            const diff = expectedTime - audio.currentTime;
            lastCalculatedDiff = diff;

            if (Date.now() > ignoreSyncUntil) {
              if (Math.abs(diff) < 0.04) {
                if (audio.playbackRate !== 1.0) audio.playbackRate = 1.0;
              } else if (Math.abs(diff) < 1.0) {
                audio.playbackRate = Math.min(Math.max(1.0 + (diff * 0.8), 0.95), 1.05);
              } else {
                audio.currentTime = expectedTime;
                audio.playbackRate = 1.0;
                ignoreSyncUntil = Date.now() + 1000;
              }
            }
            if (audio.paused && !isMuted) {
              audio.play().catch(() => {});
            }
          } else {
            if (!audio.paused) audio.pause();
            if (state.status === 'stopped') {
              audio.currentTime = 0;
              nowPlaying.innerText = "Stopped";
            }
            lastCalculatedDiff = 0;
          }
        } else {
          nowPlaying.innerText = "ALARM PLAYING...";
        }

      } catch (e) {
        // statusInd.className = "w-16 h-16 rounded-full bg-red-900 mx-auto mb-6 flex items-center justify-center transition-all";
        // document.getElementById('statusIcon').className = "fa-solid fa-triangle-exclamation text-red-500 text-xl";
        nowPlaying.innerText = "Disconnected";
      }
      setTimeout(poll, 1500);
    }
  </script>
</body>

</html>