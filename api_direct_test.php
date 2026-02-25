<?php

$apiKey = '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';
$apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

$prompt = "Generate 2 environmental tasks for: Leaking water pipe";

$data = [
    'model' => 'openrouter/auto',
    'messages' => [
        ['role' => 'system', 'content' => 'Return ONLY a JSON object with "tasks" (array of objects with "description" and "difficulty") and "suggested_priority" (string).'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'response_format' => ['type' => 'json_object']
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'HTTP-Referer: http://localhost:8000',
    'X-Title: EcoSpot Project'
]);

echo "Requesting OpenRouter...\n";
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
echo "Response: $response\n";
