<?php
// /tt/sessions_iframe_test.php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 15834;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sessions open-window test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .row { display: flex; gap: 12px; align-items: end; margin-bottom: 12px; }
    input { padding: 8px; width: 180px; }
    button { padding: 8px 12px; cursor: pointer; }
    .hint { color: #444; margin: 10px 0; }
    .mono { font-family: ui-monospace, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .box { background:#f6f7fb; border:1px solid #e5e7eb; padding:12px; border-radius:10px; }
  </style>
</head>
<body>
  <h2>Sessions open-window test</h2>

  <div class="row">
    <div>
      <label>ScheduledUnitId</label><br>
      <input id="sid" value="<?= htmlspecialchars((string)$id) ?>">
    </div>
    <div>
      <label>Param name</label><br>
      <input id="param" class="mono" value="scheduled_unit_id">
    </div>
    <button onclick="go()">Open Sessions</button>
  </div>

  <div class="hint box">
    Paradigm blocks iframes (X-Frame-Options / CSP), so this opens in a <b>new tab</b> instead.<br>
    If you’re logged in on <span class="mono">aihe.edu.net.au</span>, you should see the sessions grid.
  </div>

  <script>
    function go(){
      const id = document.getElementById('sid').value.trim();
      const p  = document.getElementById('param').value.trim() || 'scheduled_unit_id';
      const url = `https://aihe.edu.net.au/php/scheduled_unit_session_edit.php?ajax=1&${encodeURIComponent(p)}=${encodeURIComponent(id)}`;
      window.open(url, "_blank");
    }
  </script>
</body>
</html>
