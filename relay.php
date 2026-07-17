<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* ── Ensure relay table exists ── */
mysqli_query($conn, "
  CREATE TABLE IF NOT EXISTS tbl_relay (
    id         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT(11)      NOT NULL,
    relay_num  TINYINT      NOT NULL DEFAULT 1,
    relay_name VARCHAR(60)  NOT NULL DEFAULT 'Relay',
    relay_icon VARCHAR(10)  NOT NULL DEFAULT '💡',
    status     TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_relay (user_id, relay_num)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ── Ensure esp32_config table exists ── */
mysqli_query($conn, "
  CREATE TABLE IF NOT EXISTS tbl_esp32 (
    id         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT(11)      NOT NULL UNIQUE,
    esp32_ip   VARCHAR(20)  NOT NULL DEFAULT '192.168.1.100',
    esp32_port INT          NOT NULL DEFAULT 80,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ── Seed default relays ── */
$defaults = [
    [1, 'Relay 1', '💡'],
    [2, 'Relay 2', '🔌'],
    [3, 'Relay 3', '🌡️'],
    [4, 'Relay 4', '🔋'],
];
$ins = $conn->prepare("INSERT IGNORE INTO tbl_relay (user_id, relay_num, relay_name, relay_icon, status) VALUES (?,?,?,?,0)");
foreach ($defaults as [$num, $name, $icon]) {
    $ins->bind_param("iiss", $user_id, $num, $name, $icon);
    $ins->execute();
}

/* ── Seed default ESP32 config ── */
$conn->query("INSERT IGNORE INTO tbl_esp32 (user_id, esp32_ip, esp32_port) VALUES ($user_id, '192.168.1.100', 80)");

$message  = "";
$msg_type = "success";

/* ── Save ESP32 IP ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_ip"])) {
    $ip   = trim($_POST["esp32_ip"]);
    $port = (int)$_POST["esp32_port"];
    $upd  = $conn->prepare("UPDATE tbl_esp32 SET esp32_ip=?, esp32_port=? WHERE user_id=?");
    $upd->bind_param("sii", $ip, $port, $user_id);
    $upd->execute();
    $message = "ESP32 IP saved.";
    $msg_type = "success";
}

/* ── Rename relay ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["rename"])) {
    $rid      = (int)$_POST["relay_id"];
    $new_name = trim($_POST["relay_name"]);
    $new_icon = trim($_POST["relay_icon"]);
    if ($new_name !== "") {
        $upd = $conn->prepare("UPDATE tbl_relay SET relay_name=?, relay_icon=? WHERE id=? AND user_id=?");
        $upd->bind_param("ssii", $new_name, $new_icon, $rid, $user_id);
        $upd->execute();
    }
    header("Location: relay.php?msg=renamed");
    exit;
}

/* ── Fetch ESP32 config ── */
$esp_res  = $conn->query("SELECT * FROM tbl_esp32 WHERE user_id=$user_id");
$esp_conf = $esp_res->fetch_assoc();
$esp_ip   = $esp_conf["esp32_ip"]   ?? "192.168.1.100";
$esp_port = $esp_conf["esp32_port"] ?? 80;

/* ── Fetch relays ── */
$res    = $conn->query("SELECT * FROM tbl_relay WHERE user_id=$user_id ORDER BY relay_num");
$relays = [];
while ($row = $res->fetch_assoc()) $relays[] = $row;

$on_count  = count(array_filter($relays, fn($r) => $r["status"] == 1));
$off_count = count($relays) - $on_count;

/* ── Messages ── */
$msgs = [
    "renamed" => ["Relay name updated.", "success"],
];
if (isset($_GET["msg"]) && isset($msgs[$_GET["msg"]])) {
    [$message, $msg_type] = $msgs[$_GET["msg"]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relay Control — MyWeb</title>
<link rel="stylesheet" href="style.css">
<style>
.relay-card form.toggle-form { width:100%; display:flex; justify-content:center; }
.rename-form {
    display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    margin-top:12px; padding-top:12px;
    border-top:1px solid var(--border); width:100%;
}
.rename-form input[type=text] {
    flex:1; min-width:60px; padding:6px 10px;
    border:1px solid var(--border-md); border-radius:var(--radius);
    font-size:13px; outline:none;
}
.rename-form input[type=text]:focus { border-color:var(--accent); }

/* Ping indicator */
.ping-dot {
    display:inline-block; width:9px; height:9px; border-radius:50%;
    background:#D1D5DB; margin-right:6px; vertical-align:middle;
    transition:background .3s;
}
.ping-dot.online  { background:var(--green); box-shadow:0 0 0 3px rgba(22,163,74,.2); }
.ping-dot.offline { background:var(--red); }

/* Log box */
.log-box {
    background:var(--bg); border:1px solid var(--border);
    border-radius:var(--radius); padding:12px 14px;
    font-size:12px; font-family:monospace; color:var(--text-sec);
    max-height:140px; overflow-y:auto; margin-top:12px;
    line-height:1.8;
}
.log-ok   { color:var(--green); }
.log-err  { color:var(--red); }
.log-info { color:var(--accent); }

.ip-form { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
.ip-form .form-group { margin-bottom:0; }
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
      <h1>Relay Control</h1>
      <p class="text-sec">Control ESP32 relay board via WiFi</p>
    </div>
    <div class="flex gap-8">
      <button class="btn btn-ghost btn-sm" onclick="allRelays(1)">⚡ All ON</button>
      <button class="btn btn-ghost btn-sm" onclick="allRelays(0)">🔇 All OFF</button>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- ESP32 Config -->
  <div class="card" style="margin-bottom:22px;">
    <div class="card-header">
      <span style="font-size:20px;">📡</span>
      <span class="card-title">ESP32 Connection</span>
      <span id="ping-status" style="margin-left:auto;font-size:13px;color:var(--text-sec);">
        <span class="ping-dot" id="ping-dot"></span>
        <span id="ping-text">Checking...</span>
      </span>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="ip-form">
          <div class="form-group">
            <label class="form-label">ESP32 IP Address</label>
            <input type="text" name="esp32_ip" class="form-control"
                   value="<?= htmlspecialchars($esp_ip) ?>"
                   placeholder="192.168.1.100" style="width:200px;">
          </div>
          <div class="form-group">
            <label class="form-label">Port</label>
            <input type="number" name="esp32_port" class="form-control"
                   value="<?= $esp_port ?>" style="width:90px;">
          </div>
          <button name="save_ip" class="btn btn-primary btn-sm" style="margin-bottom:1px;">Save</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="pingESP32()" style="margin-bottom:1px;">🔍 Test ping</button>
        </div>
      </form>
      <div class="log-box" id="log-box">
        <span class="log-info">» Ready. ESP32 target: <?= htmlspecialchars($esp_ip) ?>:<?= $esp_port ?></span>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Total relays</div>
      <div class="stat-value"><?= count($relays) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Active (ON)</div>
      <div class="stat-value" style="color:var(--green)" id="count-on"><?= $on_count ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Inactive (OFF)</div>
      <div class="stat-value" style="color:var(--text-mut)" id="count-off"><?= $off_count ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">ESP32 IP</div>
      <div class="stat-value" style="font-size:14px;font-weight:600;padding-top:4px;"><?= htmlspecialchars($esp_ip) ?></div>
    </div>
  </div>

  <!-- Relay Cards -->
  <div class="section-title"><span>🔌</span> Relay channels</div>
  <div class="relay-grid" id="relay-grid">
    <?php foreach ($relays as $relay):
      $is_on = $relay["status"] == 1;
    ?>
    <div class="relay-card <?= $is_on ? 'on' : 'off' ?>" id="card-<?= $relay['id'] ?>">
      <div class="relay-icon"><?= $relay["relay_icon"] ?></div>
      <div class="relay-name"><?= htmlspecialchars($relay["relay_name"]) ?></div>
      <span class="relay-status" id="badge-<?= $relay['id'] ?>"><?= $is_on ? 'ON' : 'OFF' ?></span>

      <div class="toggle-form">
        <div class="toggle-wrap">
          <span class="text-sm text-sec">OFF</span>
          <label class="toggle">
            <input type="checkbox"
                   id="toggle-<?= $relay['id'] ?>"
                   <?= $is_on ? 'checked' : '' ?>
                   onchange="toggleRelay(<?= $relay['id'] ?>, <?= $relay['relay_num'] ?>, this.checked ? 1 : 0)">
            <span class="toggle-slider"></span>
          </label>
          <span class="text-sm text-sec">ON</span>
        </div>
      </div>

      <!-- Rename -->
      <form method="POST" class="rename-form">
        <input type="hidden" name="relay_id" value="<?= $relay['id'] ?>">
        <input type="text" name="relay_icon"
               value="<?= htmlspecialchars($relay['relay_icon']) ?>"
               style="max-width:40px;text-align:center;" placeholder="💡" title="Emoji icon">
        <input type="text" name="relay_name"
               value="<?= htmlspecialchars($relay['relay_name']) ?>" placeholder="Name">
        <button name="rename" class="btn btn-ghost btn-sm">Save</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Arduino Code -->
  <div class="card mt-24">
    <div class="card-header">
      <span style="font-size:20px;">🛠️</span>
      <span class="card-title">ESP32 Arduino Code</span>
    </div>
    <div class="card-body">
      <p class="text-sec text-sm" style="margin-bottom:12px;">
        Upload this sketch to your ESP32. It starts a web server on port <?= $esp_port ?> and controls 4 relays via GPIO.
      </p>
      <pre style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:16px;font-size:12px;overflow-x:auto;line-height:1.7;"><code><?php echo htmlspecialchars(
'#include <WiFi.h>
#include <WebServer.h>

const char* ssid     = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// GPIO pins for Relay 1-4 (change to match your wiring)
const int RELAY_PINS[4] = {26, 27, 14, 12};

WebServer server(' . $esp_port . ');

void handleRelay() {
  if (!server.hasArg("num") || !server.hasArg("status")) {
    server.send(400, "text/plain", "Missing args");
    return;
  }
  int num    = server.arg("num").toInt();    // 1-4
  int status = server.arg("status").toInt(); // 0 or 1

  if (num < 1 || num > 4) {
    server.send(400, "text/plain", "Invalid relay num");
    return;
  }

  int pin = RELAY_PINS[num - 1];
  // Most relay modules are active-LOW: LOW = ON, HIGH = OFF
  digitalWrite(pin, status == 1 ? LOW : HIGH);

  Serial.printf("Relay %d -> %s\n", num, status ? "ON" : "OFF");
  server.send(200, "text/plain", "OK");
}

void handleStatus() {
  String json = "{";
  for (int i = 0; i < 4; i++) {
    // LOW = ON for active-low relay
    int st = (digitalRead(RELAY_PINS[i]) == LOW) ? 1 : 0;
    json += "\"relay" + String(i+1) + "\":" + String(st);
    if (i < 3) json += ",";
  }
  json += "}";
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.send(200, "application/json", json);
}

void handlePing() {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.send(200, "text/plain", "pong");
}

void setup() {
  Serial.begin(115200);
  for (int i = 0; i < 4; i++) {
    pinMode(RELAY_PINS[i], OUTPUT);
    digitalWrite(RELAY_PINS[i], HIGH); // all OFF at start
  }

  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500); Serial.print(".");
  }
  Serial.println();
  Serial.print("ESP32 IP: ");
  Serial.println(WiFi.localIP());

  server.on("/relay",  handleRelay);
  server.on("/status", handleStatus);
  server.on("/ping",   handlePing);
  server.begin();
  Serial.println("HTTP server started");
}

void loop() {
  server.handleClient();
}'); ?></code></pre>
      <p class="text-sec text-sm mt-8">
        📌 After upload, open Serial Monitor at 115200 baud to see the ESP32's IP address, then paste it above.
      </p>
    </div>
  </div>
</div>

<script>
const ESP_IP   = <?= json_encode($esp_ip) ?>;
const ESP_PORT = <?= (int)$esp_port ?>;
const BASE_URL = `http://${ESP_IP}:${ESP_PORT}`;

/* ── Logging ── */
function log(msg, type = 'info') {
  const box = document.getElementById('log-box');
  const ts  = new Date().toLocaleTimeString();
  const cls = type === 'ok' ? 'log-ok' : type === 'err' ? 'log-err' : 'log-info';
  box.innerHTML += `<br><span class="${cls}">[${ts}] ${msg}</span>`;
  box.scrollTop = box.scrollHeight;
}

/* ── Ping ESP32 ── */
async function pingESP32() {
  const dot  = document.getElementById('ping-dot');
  const text = document.getElementById('ping-text');
  dot.className = 'ping-dot'; text.textContent = 'Pinging...';
  log(`Pinging ${BASE_URL}/ping ...`);
  try {
    const res = await fetch(`${BASE_URL}/ping`, { signal: AbortSignal.timeout(3000) });
    if (res.ok) {
      dot.className = 'ping-dot online'; text.textContent = 'Online';
      log('ESP32 is online ✓', 'ok');
    } else {
      throw new Error('Bad response');
    }
  } catch(e) {
    dot.className = 'ping-dot offline'; text.textContent = 'Offline';
    log(`ESP32 not reachable: ${e.message}`, 'err');
  }
}

/* ── Save relay status to DB via AJAX ── */
async function saveStatusDB(relayId, status) {
  await fetch('relay_update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `relay_id=${relayId}&status=${status}`
  });
}

/* ── Update card UI ── */
function updateCard(relayId, status) {
  const card  = document.getElementById(`card-${relayId}`);
  const badge = document.getElementById(`badge-${relayId}`);
  const chk   = document.getElementById(`toggle-${relayId}`);
  if (status === 1) {
    card.className  = 'relay-card on';
    badge.textContent = 'ON';
  } else {
    card.className  = 'relay-card off';
    badge.textContent = 'OFF';
  }
  chk.checked = status === 1;
  updateCounts();
}

function updateCounts() {
  const cards = document.querySelectorAll('.relay-card');
  let on = 0;
  cards.forEach(c => { if (c.classList.contains('on')) on++; });
  document.getElementById('count-on').textContent  = on;
  document.getElementById('count-off').textContent = cards.length - on;
}

/* ── Toggle single relay ── */
async function toggleRelay(relayId, relayNum, status) {
  const url = `${BASE_URL}/relay?num=${relayNum}&status=${status}`;
  log(`Sending → ${url}`);
  updateCard(relayId, status); // optimistic UI update
  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(4000) });
    if (res.ok) {
      log(`Relay ${relayNum} → ${status ? 'ON' : 'OFF'} ✓`, 'ok');
      await saveStatusDB(relayId, status);
    } else {
      throw new Error(`HTTP ${res.status}`);
    }
  } catch(e) {
    log(`Error: ${e.message} — rolling back`, 'err');
    updateCard(relayId, status ? 0 : 1); // rollback
    document.getElementById(`toggle-${relayId}`).checked = !status;
  }
}

/* ── All ON / All OFF ── */
async function allRelays(status) {
  log(`Setting all relays ${status ? 'ON' : 'OFF'} ...`);
  const toggles = document.querySelectorAll('[id^="toggle-"]');
  for (const t of toggles) {
    const relayId  = t.id.split('-')[1];
    const relayNum = [...document.querySelectorAll('[id^="toggle-"]')].indexOf(t) + 1;
    await toggleRelay(relayId, relayNum, status);
    await new Promise(r => setTimeout(r, 150)); // small delay between commands
  }
}

/* ── Auto-ping on load ── */
window.addEventListener('load', () => setTimeout(pingESP32, 800));
</script>
</body>
</html>