<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$openai_api_key = $_ENV['OPENAI_API_KEY'];

if (!$openai_api_key) {
    throw new Exception("OPENAI_API_KEY not found in .env file");
}

enum StreamType: int {
    case Event = 0; // for JavaScript EventSource
    case Plain = 1; // for terminal application
    case Raw   = 2; // for raw data from ChatGPT API
}

class ChatGPT {
    protected array $messages = [];
    protected array $functions = [];
    protected $savefunction = null;
    protected $loadfunction = null;
    protected bool $loaded = false;
    protected $tool_choice = "auto";
    protected string $model = "gpt-3.5-turbo";
    protected array $params = [];
    protected bool $assistant_mode = false;
    protected ?Assistant $assistant = null;
    protected ?string $thread_id = null;
    protected ?Run $run = null;

    public function __construct(
        string $api_key,
        protected ?string $chat_id = null
    ) {
        if( $this->chat_id === null ) {
            $this->chat_id = uniqid( more_entropy: true );
        }
    }

    public function umessage( string $user_message ) {
        $message = [
            "role" => "user",
            "content" => $user_message,
        ];

        $this->messages[] = $message;

        if( $this->assistant_mode ) {
            $this->add_assistants_message( $message );
        }

        if( is_callable( $this->savefunction ) ) {
            ($this->savefunction)( (object) $message, $this->chat_id );
        }
    }

    protected function get_function( string $function_name ): callable|false {
        if( $this->assistant_mode ) {
            $functions = $this->assistant->get_functions();
        } else {
            $functions = $this->functions;
        }

        foreach( $functions as $function ) {
            if( $function["name"] === $function_name ) {
                return $function["function"] ?? $function["name"];
            }
        }

        return false;
    }

    public function response(
        bool $raw_function_response = false,
        ?StreamType $stream_type = null,
    ) {
        if( $this->assistant_mode ) {
            return $this->assistant_response(
                $raw_function_response,
                $stream_type, // TODO: streaming is not supported yet
            );
        }

        $params = [
            "model" => $this->model,
            "messages" => $this->messages,
        ];

        $params = array_merge( $params, $this->params );

        $functions = $this->get_functions();

        if( ! empty( $functions ) ) {
            $params["tools"] = $functions;
            $params["tool_choice"] = $this->tool_choice;
        }

        // make ChatGPT API request
        $ch = curl_init( "https://api.openai.com/v1/chat/completions" );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->api_key
        ] );

        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        if( $stream_type ) {
            $params["stream"] = true;

            $response_text = "";

            curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function( $ch, $data ) use ( &$response_text, $stream_type ) {
                $response_text .= $this->parse_stream_data( $ch, $data, $stream_type );

                if( connection_aborted() ) {
                    return 0;
                }

                return strlen( $data );
            } );
        }

        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode(
            $params
        ) );

        $curl_exec = curl_exec( $ch );

        // get ChatGPT reponse
        if( $stream_type ) {
            if( $stream_type === StreamType::Event ) {
                echo "event: stop\n";
                echo "data: stopped\n\n";
            }

            $message = new stdClass;
            $message->role = "assistant";
            $message->content = $response_text;
        } else {
            $response = json_decode( $curl_exec );

            // somewhat handle errors
            if( ! isset( $response->choices[0]->message ) ) {
                if( isset( $response->error ) ) {
                    $error = trim( $response->error->message . " (" . $response->error->type . ")" );
                } else {
                    $error = $curl_exec;
                }
                throw new \Exception( "Error in OpenAI request: " . $error );
            }

            // add response to messages
            $message = $response->choices[0]->message;
        }

        $this->messages[] = $message;

        if( is_callable( $this->savefunction ) ) {
            ($this->savefunction)( (object) $message, $this->chat_id );
        }

        $message = end( $this->messages );

        $message = $this->handle_functions( $message, $raw_function_response );

        return $message;
    }

    // Use $this->apiKey when making requests to the OpenAI API
}

//$chatGPT = new ChatGPT($openai_api_key);