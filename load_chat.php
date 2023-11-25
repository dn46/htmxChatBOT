<?php

$chat_id = $_GET['chat_id']; // the chat id is passed in the url
//here we will load the chats from the message history

$chat = json_decode(
    file_get_contents(
        "chats/$chat_id.json" // the file name is the chat id
    ),
    true // decode as an associative array
);  

// we print out the chats
echo '<div class="messages">';
foreach ($chat["messages"] as $message) {
    echo '<div class="message"'.$message['role'].' message">'.htmlspecialchars($message["content"]).'</div>'; // here we print out the message content and the role
}
echo '</div>';

?>

<!-- here we will add the form to send new messages -->
<form hx-post="/send_message.php" hx-target=".messages" hx-swap="beforeend" hx-on::after-request=" if(event.detail.successful) this.reset()"> <!-- hx-post is the url to send the message to; hx-target is the element to load the response into; hx-swap is the position to load the response into; hx-on::after-request makes the input field in the form clear after a successfull request -->
    <input type="hidden" name="chat_id" value="<?= htmlspecialchars($_GET['chat_id']); ?>">
    <input type="text" name="message" placeholder="Type a message...">
    <button>Send</button>
</form>