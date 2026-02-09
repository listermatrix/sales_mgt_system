<?php

namespace App\Traits;

use App\Constants\HttpStatusCode;
use Illuminate\Http\JsonResponse;

/**
 * API Response Trait
 *
 * Provides consistent API response formatting
 */
trait ApiResponse
{
    /**
     * Send a success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = HttpStatusCode::OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Send an error response
     *
     * @param string $message
     * @param string $errorCode
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        string $errorCode,
        int $statusCode = HttpStatusCode::BAD_REQUEST,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $errorCode,
            ],
        ];

        if ($errors) {
            $response['error']['details'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send a validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => 'VALIDATION_ERROR',
                'details' => $errors,
            ],
        ], HttpStatusCode::UNPROCESSABLE_ENTITY);
    }

    /**
     * Send a not found response
     *
     * @param string $message
     * @param string $errorCode
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $message = 'Resource not found',
        string $errorCode = 'RESOURCE_NOT_FOUND'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $errorCode,
            HttpStatusCode::NOT_FOUND
        );
    }

    /**
     * Send a created response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse(
        mixed $data,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse(
            $data,
            $message,
            HttpStatusCode::CREATED
        );
    }

    /**
     * Send a no content response
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, HttpStatusCode::NO_CONTENT);
    }

    /**
     * Send an unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            'UNAUTHORIZED',
            HttpStatusCode::UNAUTHORIZED
        );
    }

    /**
     * Send a forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            'FORBIDDEN',
            HttpStatusCode::FORBIDDEN
        );
    }

    /**
     * Send a conflict response
     *
     * @param string $message
     * @param string $errorCode
     * @return JsonResponse
     */
    protected function conflictResponse(
        string $message,
        string $errorCode = 'CONFLICT'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $errorCode,
            HttpStatusCode::CONFLICT
        );
    }

    /**
     * Send a server error response
     *
     * @param string $message
     * @param string $errorCode
     * @return JsonResponse
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error',
        string $errorCode = 'SERVER_ERROR'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $errorCode,
            HttpStatusCode::INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Paginated response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(
        mixed $data,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
            'message' => $message,
        ], HttpStatusCode::OK);
    }
}
