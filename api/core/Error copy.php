<?php

class Error {

    // Common HTTP status codes
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const INTERNAL_SERVER_ERROR = 500;

    /**
     * Throw a JSON error response.
     *
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @param array $details Additional error details (optional)
     */
    public static function throw($statusCode, $message, $details = []) {
        // Set the HTTP response code
        http_response_code($statusCode);

        // Construct the error response
        $response = [
            'error' => [
                'code' => $statusCode,
                'message' => $message,
            ]
        ];

        // Add additional details if provided
        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        // Set the content type to JSON
        header('Content-Type: application/json');

        // Output the JSON response
        echo json_encode($response, JSON_PRETTY_PRINT);

        // Stop further execution
        exit;
    }

    /**
     * Throw a validation error response.
     *
     * @param array $errors Validation errors (e.g., field => message)
     */
    public static function throwValidationError($errors) {
        self::throw(self::BAD_REQUEST, 'Validation failed', $errors);
    }

    /**
     * Throw a not found error response.
     *
     * @param string $resourceName Name of the resource not found
     */
    public static function throwNotFound($resourceName) {
        self::throw(self::NOT_FOUND, "The requested resource '$resourceName' was not found.");
    }

    /**
     * Throw an internal server error response.
     *
     * @param string $message Optional custom error message
     */
    public static function throwInternalServerError($message = 'Internal Server Error') {
        self::throw(self::INTERNAL_SERVER_ERROR, $message);
    }

    /**
     * Throw an unauthorized error response.
     *
     * @param string $message Optional custom error message
     */
    public static function throwUnauthorized($message = 'Unauthorized') {
        self::throw(self::UNAUTHORIZED, $message);
    }

    /**
     * Throw a forbidden error response.
     *
     * @param string $message Optional custom error message
     */
    public static function throwForbidden($message = 'Forbidden') {
        self::throw(self::FORBIDDEN, $message);
    }

    /**
     * Throw a method not allowed error response.
     *
     * @param string $message Optional custom error message
     */
    public static function throwMethodNotAllowed($message = 'Method Not Allowed') {
        self::throw(self::METHOD_NOT_ALLOWED, $message);
    }
}

// Example usage
// Error::throwValidationError(['email' => 'The email field is required.']);
// Error::throwNotFound('User');
// Error::throwInternalServerError();
?>