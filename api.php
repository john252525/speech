<?php

require_once __DIR__.'/config.php';

header('Content-Type: application/json');

// Конфигурация
$config = [
    'api_key' => YANDEX_SPEECH_API_KEY,
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_audio_types' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
    'log_errors' => true
];

// Обработка ошибок
function handleError($message, $code = 400) {
    http_response_code($code);
    die(json_encode(['error' => $message]));
}

// Логирование
function logError($message) {
    if ($GLOBALS['config']['log_errors']) {
        file_put_contents(__DIR__.'/tmp/_speech_api_errors.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
    }
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError('Only POST requests are allowed', 405);
}




// Получение данных
//$action = $_REQUEST['action'] ?? '';
//$text      = $_POST['text']   ?? '';
//$audioData = $_FILES['audio'] ?? null;

$action = @trim($_GET['action']);  // speech2text или text2speech
$data = json_decode(file_get_contents('php://input'), true);
$text = $data['text'] ?? '';
$audioData = null;
if(!empty($data['audio'])){
    $temp_file = tempnam(sys_get_temp_dir(), 'voice_') . '.oga';
    file_put_contents($temp_file, base64_decode($data['audio']));
    $audioData = ['size'=>100,'type'=>'audio/ogg','tmp_name'=>$temp_file];
}




// Обработка запросов
switch ($action) {
    case 'speech2text':
        if (!$audioData) {
            handleError('No audio file provided');
        }
        
        // Проверка размера файла
        if ($audioData['size'] > $config['max_file_size']) {
            handleError('File size exceeds maximum allowed');
        }
        
        // Проверка типа файла
        if (!in_array($audioData['type'], $config['allowed_audio_types'])) {
            handleError('Invalid audio file type');
        }
        
        // // Здесь должна быть интеграция с выбранным сервисом speech2text
        // // Например, через cURL к API Yandex, Google или другому провайдеру
        
        // // Заглушка для примера - в реальности замените на вызов API
        // $result = [
        //     'text' => 'Это пример распознанного текста. В реальности здесь будет результат от API.',
        //     'confidence' => 0.95
        // ];
        
        // echo json_encode($result);
        
        
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://stt.api.cloud.yandex.net/speech/v1/stt:recognize?lang=ru-RU');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Api-Key ' . $config['api_key'],
            'Content-Type: audio/ogg'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($audioData['tmp_name']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['result'])) {
            echo json_encode(['text' => $result['result']]);
        } else {
            handleError('Speech recognition failed');
        }        
        
        
        
        
        
        
        
        
        break;
        
    case 'text2speech':
        if (empty($text)) {
            handleError('No text provided');
        }
        
        // // Здесь должна быть интеграция с выбранным сервисом text2speech
        // // Например, через cURL к API Yandex, Google или другому провайдеру
        
        // // Заглушка для примера - в реальности замените на вызов API
        // $audioContent = base64_encode('Здесь будет бинарное содержимое аудиофайла');
        
        // echo json_encode([
        //     'audio' => $audioContent,
        //     'format' => 'mp3'
        // ]);






        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Api-Key ' . $config['api_key']
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'text' => $text,
            'lang' => 'ru-RU',
            'voice' => YANDEX_SPEECH_VOICE,
            'format' => 'oggopus'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $audioContent = curl_exec($ch);
        curl_close($ch);


      //$filename = time() . '.ogg';
        $filename = time() . '.' . uniqid('', true) . '.ogg';
        file_put_contents(__DIR__.'/tmp/'.$filename, $audioContent);  //// вроде просто для отладки делал сохранял результат озвучки, можно закомментить


        echo json_encode([
            'voice' => base64_encode($audioContent),  ////////////////// 2025-08-01
          
          //// 2025-09-26 закомментировал просто потому что вроде нигде не используется снаружи
          //'audio' => base64_encode($audioContent),
          //'format' => 'ogg',
          //'filename' => $filename,
        ]);









        break;
        
    default:
        handleError('Invalid action specified');
}
