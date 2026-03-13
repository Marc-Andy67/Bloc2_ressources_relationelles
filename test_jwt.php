<?php

$url = 'http://127.0.0.1:8000/api/login_check';
$data = ['email' => 'test1@example.com', 'password' => 'Test1234!'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // Permet de récupérer le body même sur erreur HTTP (400, 401)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "LOGIN RESULT:\n";
echo $result . "\n\n";

$tokenData = json_decode($result, true);
if (isset($tokenData['token'])) {
    $token = $tokenData['token'];
    
    // Test du profil
    $profileUrl = 'http://127.0.0.1:8000/api/user/profile';
    $profileOptions = [
        'http' => [
            'header'  => "Content-type: application/json\r\nAuthorization: Bearer $token\r\n",
            'method'  => 'PUT',
            'content' => json_encode(['name' => 'John Doe Auth Test']),
            'ignore_errors' => true
        ]
    ];
    
    $profileContext = stream_context_create($profileOptions);
    $profileResult = file_get_contents($profileUrl, false, $profileContext);
    
    echo "PROFILE UPDATE RESULT:\n";
    echo $profileResult . "\n";
}
