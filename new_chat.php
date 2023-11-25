<?php 

if (! file_exists("chats")) {
    mkdir("chats");
}

$chat_id = uniqid();

$chat = [
    "name" => "Untitled chat",
    "id" => $chat_id,
    "messages" => []
];

file_put_contents(
    "chats/" . $chat_id . ".json", // a file in the chats directory with the chat id as the name
     json_encode($chat) // the chat data as a json string
);

echo '<button hx-get="/load_chat.php?chat_id='.$chat_id.'" hx-target="main">' . htmlspecialchars($chat["name"]) . '</button>'; // the button that will open the chat; it takes the name from the chat data; hx-get is the url to load the chat; hx-target is the element to load the chat into (the main element)