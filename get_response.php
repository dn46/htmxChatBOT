<?php

require ("ChatGPT.php");

$chat_id = $_GET['chat_id']; // the chat id is passed in the url
//here we will load the chats from the message history

$chat = json_decode(
    file_get_contents(
        "chats/$chat_id.json" // the file name is the chat id
    ),
    true // decode as an associative array
);

// we get the latest message from the chat history

$message = end($chat["messages"]);

// we get send the message to the chatbot

$chatgpt = new ChatGPT($openai_api_key, $_GET['chat_id'] );
$chatgpt->umessage( $message["content"] );

// we get the response from the chatbot

$response = (array)$chatgpt->response();

// we add the new message to the chat history

$chat["messages"][] = $response;

file_put_contents(
    "chats/" . $_GET["chat_id"] . ".json", // the file name is the chat id
    json_encode($chat) // the chat data as a json string
);

echo '<div class="assistant message">'. "GPTITI -> ".htmlspecialchars($response["content"]).'</div>';