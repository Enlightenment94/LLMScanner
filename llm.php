<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . "/config.php";

use Orhanerday\OpenAi\OpenAi;

function apiRequest($content){
    $open_ai = new OpenAi($GLOBALS['api']);

    // Wywołanie API z nowym promptem
    $chat = $open_ai->chat([
        'model' => 'gpt-3.5-turbo',
        //'model' => 'gpt-4',
        'messages' => [
            [
                "role" => "system",
                "content" => "You are a highly skilled cybersecurity expert."
            ],
            [
                "role" => "user",
                "content" => $content . "\n\nBased on your analysis, respond only with 'Yes' or 'No'. If there is a high probability that the code is malicious, respond with 'Yes'. Do not provide any additional explanation."
            ],
        ],
        'temperature' => 0.2, 
        'max_tokens' => 10,  
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ]);

    $d = json_decode($chat);

    if (isset($d->choices) && is_array($d->choices) && count($d->choices) > 0) {
        $response = trim($d->choices[0]->message->content);
        return $response;
    } else {
        echo "Brak odpowiedzi od API lub wystąpił błąd.";
        return null;
    }
}

