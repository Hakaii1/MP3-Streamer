<?php
// api_state.php
// Updated for Server-Side DJ Logic + Remote Restart

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
$stateFile = __DIR__ . '/state.json';
$alarmsFile = __DIR__ . '/alarms.json';

// --- 1. ALARM LOGIC (Unchanged) ---
function checkAlarms($alarmsFile)
{
    if (!file_exists($alarmsFile))
        return ['event' => null, 'trigger_pause' => false];
    $fp = fopen($alarmsFile, 'c+');
    if (!$fp)
        return ['event' => null, 'trigger_pause' => false];
    $eventResult = null;
    $shouldPause = false;
    if (flock($fp, LOCK_EX)) {
        $fsize = filesize($alarmsFile);
        $json = $fsize > 0 ? fread($fp, $fsize) : '{}';
        $alarms = json_decode($json, true) ?? ['slots' => [], 'last_event' => null];
        try {
            $tz = new DateTimeZone('Asia/Singapore');
        } catch (Exception $e) {
            $tz = new DateTimeZone('UTC');
        }
        $nowObj = new DateTime('now', $tz);
        $today = $nowObj->format('Y-m-d');
        $hhmm = $nowObj->format('H:i');
        $changed = false;
        foreach ($alarms['slots'] as &$s) {
            if (empty($s['enabled']))
                continue;
            if (($s['time'] ?? '') === $hhmm && ($s['last_fired'] ?? null) !== $today) {
                $alarms['last_event'] = ['id' => uniqid('a_'), 'slot_id' => $s['id'], 'title' => $s['title'], 'file' => $s['file'], 'fired_at' => time()];
                $s['last_fired'] = $today;
                $changed = true;
                $shouldPause = true;

                // --- NEW: Auto-Delete TTS Files ---
                if (strpos($s['file'], 'tts/') === 0) {
                    // Remove this slot entirely, but keep the file until played
                    $s['to_delete'] = true;
                }
            }
        }

        // Remove marked slots
        if ($changed) {
            $alarms['slots'] = array_values(array_filter($alarms['slots'], function ($s) {
                return !isset($s['to_delete']);
            }));
        }

        if (isset($alarms['last_event']) && (time() - ($alarms['last_event']['fired_at'] ?? 0) < 45))
            $eventResult = $alarms['last_event'];
        if ($changed) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($alarms, JSON_PRETTY_PRINT));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return ['event' => $eventResult, 'trigger_pause' => $shouldPause];
}
$alarmCheck = checkAlarms($alarmsFile);
$alarmEvent = $alarmCheck['event'];

// --- 2. STATE HANDLING ---
function withStateLock($file, callable $callback)
{
    $fp = fopen($file, 'c+');
    if (!$fp)
        return $callback([]);
    if (flock($fp, LOCK_EX)) {
        $fsize = filesize($file);
        $content = $fsize > 0 ? fread($fp, $fsize) : '';
        $data = json_decode($content, true) ?? ['global' => [], 'clients' => []];

        // Ensure Default State includes QUEUE
        $defaults = ['track' => '', 'status' => 'stopped', 'start_ts' => 0, 'pause_seek' => 0, 'volume' => 1, 'queue' => []];
        foreach ($defaults as $k => $v) {
            if (!isset($data['global'][$k]))
                $data['global'][$k] = $v;
        }

        $newData = $callback($data);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($newData, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $newData ?? [];
}

// GET REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $clientId = $_GET['client_id'] ?? null;
    $clientName = $_GET['name'] ?? null;
    $clientDiff = $_GET['diff'] ?? null;

    $state = withStateLock($stateFile, function ($d) use ($clientId, $clientName, $clientDiff) {
        if ($clientId) {
            $isNew = !isset($d['clients'][$clientId]);
            $d['clients'][$clientId]['last_seen'] = time();
            if ($clientName)
                $d['clients'][$clientId]['name'] = $clientName;
            elseif (!isset($d['clients'][$clientId]['name']))
                $d['clients'][$clientId]['name'] = "Device " . substr($clientId, 0, 4);
            if ($clientDiff !== null)
                $d['clients'][$clientId]['diff'] = floatval($clientDiff);

            // Sync new clients immediately
            if ($isNew || empty($d['clients'][$clientId]['track'])) {
                foreach (['track', 'status', 'start_ts', 'pause_seek'] as $k)
                    $d['clients'][$clientId][$k] = $d['global'][$k];
                // New devices always start at MAX volume
                $d['clients'][$clientId]['volume'] = 1;
            }
        }
        foreach (($d['clients'] ?? []) as $k => $c) {
            if (time() - ($c['last_seen'] ?? 0) > 15)
                unset($d['clients'][$k]);
        }
        return $d;
    });

    $response = ($clientId && isset($state['clients'][$clientId])) ? $state['clients'][$clientId] : $state;
    $response['alarm_event'] = $alarmEvent;
    $response['server_time'] = microtime(true);
    if (!isset($response['global']))
        $response['global'] = $state['global'];
    echo json_encode($response);
    exit;
}

// POST REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reqTrack = $_POST['track'] ?? null;
    $seekVal = $_POST['seek'] ?? null;
    $target = $_POST['target'] ?? 'global';
    $queueJson = $_POST['queue'] ?? null;

    withStateLock($stateFile, function ($state) use ($action, $reqTrack, $seekVal, $target, $queueJson) {
        $now = microtime(true);

        $updateObj = function (&$g) use ($action, $reqTrack, $seekVal, $now, $queueJson) {
            // --- NEW: Handle Queue Updates ---
            if ($queueJson) {
                $decoded = json_decode($queueJson, true);
                if (is_array($decoded))
                    $g['queue'] = $decoded;
            }

            if ($action === 'play_at') {
                if ($reqTrack)
                    $g['track'] = $reqTrack;
                $g['status'] = 'playing';
                $g['start_ts'] = $now - (float) $seekVal;
                $g['pause_seek'] = 0;
            } elseif ($action === 'play') {
                if ($reqTrack && $reqTrack !== ($g['track'] ?? '')) {
                    $g['track'] = $reqTrack;
                    $g['start_ts'] = $now;
                    $g['pause_seek'] = 0;
                } elseif (($g['status'] ?? '') === 'paused') {
                    $g['start_ts'] = $now - ($g['pause_seek'] ?? 0);
                } elseif (($g['status'] ?? '') === 'stopped') {
                    $g['start_ts'] = $now;
                }
                $g['status'] = 'playing';
            } elseif ($action === 'pause') {
                if (($g['status'] ?? '') === 'playing')
                    $g['pause_seek'] = $now - ($g['start_ts'] ?? $now);
                $g['status'] = 'paused';
            } elseif ($action === 'stop') {
                $g['status'] = 'stopped';
                $g['start_ts'] = 0;
                $g['pause_seek'] = 0;
            }
            // --- NEW: RESTART LOGIC ---
            elseif ($action === 'restart') {
                $g['restart_ts'] = $now;
            } elseif ($action === 'volume') {
                $g['volume'] = floatval($_POST['volume'] ?? 1.0);
            } elseif ($action === 'seek') {
                if ($seekVal !== null) {
                    $g['start_ts'] = $now - (float) $seekVal;
                    if (($g['status'] ?? '') === 'paused')
                        $g['pause_seek'] = (float) $seekVal;
                }
            }
            // --- CHANGED ---
            // If a client says "track_finished", Server advances the playlist
            elseif ($action === 'track_finished') {
                $reportedTrack = $reqTrack;
                $currentTrack = $g['track'] ?? '';

                // Security: Only skip if the reported track matches current (avoids race conditions)
                if ($reportedTrack === $currentTrack && !empty($g['queue'])) {
                    $idx = array_search($currentTrack, $g['queue']);
                    if ($idx !== false) {
                        $nextIdx = $idx + 1;
                        // Loop Logic: Go back to 0 if at end
                        if ($nextIdx >= count($g['queue'])) {
                            $nextIdx = 0;
                        }

                        $g['track'] = $g['queue'][$nextIdx];
                        $g['start_ts'] = $now;
                        $g['pause_seek'] = 0;
                        $g['status'] = 'playing';
                    }
                }
            }
        };

        if ($target === 'global') {
            $updateObj($state['global']);
            // Sync clients to global
            // Sync clients to global
            foreach ($state['clients'] as &$c) {
                // Sync playback state
                $c['track'] = $state['global']['track'];
                $c['status'] = $state['global']['status'];
                $c['start_ts'] = $state['global']['start_ts'];
                $c['pause_seek'] = $state['global']['pause_seek'];

                // Only sync volume if explicitly requested
                if ($action === 'volume') {
                    $c['volume'] = $state['global']['volume'];
                }
            }
        } elseif (isset($state['clients'][$target])) {
            $updateObj($state['clients'][$target]);
        }
        return $state;
    });
    echo json_encode(['ok' => true]);
    exit;
}
