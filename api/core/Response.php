<?php

class Response {

    // Common HTTP status codes
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;

    /**
     * Send a JSON response.
     *
     * @param int $statusCode HTTP status code
     * @param array $data Response data
     * @param string $message Optional message
     */
    public static function send($statusCode, $data = [], $message = '') {
        // Set the HTTP response code
        http_response_code($statusCode);

        // Construct the response
        $response = [
            'status' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];

        // Remove empty fields
        if (empty($message)) {
            unset($response['message']);
        }
        if (empty($data)) {
            unset($response['data']);
        }

        // Set the content type to JSON
        header('Content-Type: application/json');

        // Output the JSON response
        echo json_encode($response, JSON_PRETTY_PRINT);

        // Stop further execution
        exit;
    }

    /**
     * Send a success response with data.
     *
     * @param array $data Response data
     * @param string $message Optional message
     */
    public static function success($data = [], $message = '') {
        self::send(self::OK, $data, $message);
    }

    /**
     * Send a resource created response.
     *
     * @param array $data Response data
     * @param string $message Optional message
     */
    public static function created($data = [], $message = 'Resource created successfully') {
        self::send(self::CREATED, $data, $message);
    }

    /**
     * Send a no content response.
     */
    public static function noContent() {
        self::send(self::NO_CONTENT);
    }
}

// Example usage
// Response::success(['id' => 1, 'name' => 'John Doe'], 'User retrieved successfully');
// Response::created(['id' => 2], 'User created successfully');
// Response::noContent();
?>