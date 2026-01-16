<?php
// /tt/sessions_popup.php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die("Missing id"); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Scheduled Unit Sessions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#f6f7fb; }
    header { padding:14px 18px; background:#fff; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;}
    .tag { padding:6px 10px; border-radius:999px; background:#111827; color:#fff; font-weight:700; font-size:13px; }
    iframe { width:100%; height: calc(100vh - 56px); border:0; }
  </style>
</head>
<body>
<header>
  <div class="tag">ScheduledUnitId: <?= htmlspecialchars((string)$id) ?></div>
  <a href="javascript:window.close();" style="text-decoration:none;font-size:22px;">✕</a>
</header>

<iframe src="https://aihe.edu.net.au/php/scheduled_unit_session_edit.php?ajax=1&scheduled_unit_id=<?= urlencode((string)$id) ?>"></iframe>
</body>
</html>
