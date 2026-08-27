<?php
/**
 * User Preferences Helper
 * Simpan file ini di root project (sejajar config.php)
 */

if(!function_exists('getUserPrefs')){
    function getUserPrefs($conn, $userId) {
        $defaults = [
            'theme' => 'dark',
            'font_size' => 'medium',
            'email_notif' => '1',
            'booking_reminder' => '1',
            'promo_notif' => '1',
            'reminder_time' => '60',
            'favorite_barber' => null
        ];
        
        if(empty($userId)) return $defaults;
        
        $stmt = mysqli_prepare($conn, "SELECT theme, font_size, email_notif, booking_reminder, promo_notif, reminder_time, favorite_barber FROM users WHERE id = ?");
        if(!$stmt) return $defaults;
        
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if(!$user) return $defaults;
        
        // Merge dengan default untuk kolom yang null
        foreach($user as $key => $value){
            if($value !== null){
                $defaults[$key] = $value;
            }
        }
        
        return $defaults;
    }
}

if(!function_exists('getUserTheme')){
    function getUserTheme($conn, $userId) {
        $prefs = getUserPrefs($conn, $userId);
        return $prefs['theme'];
    }
}

if(!function_exists('getUserFontSize')){
    function getUserFontSize($conn, $userId) {
        $prefs = getUserPrefs($conn, $userId);
        return $prefs['font_size'];
    }
}

if(!function_exists('outputHtmlAttrs')){
    function outputHtmlAttrs($conn, $userId) {
        $prefs = getUserPrefs($conn, $userId);
        echo 'data-theme="' . htmlspecialchars($prefs['theme']) . '" ';
        echo 'data-font="' . htmlspecialchars($prefs['font_size']) . '"';
    }
}
?>