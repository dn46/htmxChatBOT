<?php

// we load the chat messages

$chat = json_decode(
    file_get_contents(
        "chats/" . $_POST["chat_id"]. ".json" // the file name is the chat id
    ),
    true // decode as an associative array
);

// we add the new message to the chat history

$chat["messages"][] = [
    "role" => "user",
    "content" => $_POST["message"]
];

// we save the chat history

file_put_contents(
    "chats/" . $_POST["chat_id"] . ".json", // the file name is the chat id
    json_encode($chat) // the chat data as a json string
);

// return an html element of the latest message

//here we must pass the chat id to the url so that the server knows which chat to load the messages from
echo '<div class="user message" hx-trigger="load" hx-get="/get_response.php?chat_id='.htmlspecialchars($_POST['chat_id']).'" hx-target=".messages" hx-swap="beforeend">'. 'User->' . htmlspecialchars($_POST["message"]).'</div>';
