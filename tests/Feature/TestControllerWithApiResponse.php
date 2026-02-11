<?php
namespace Tests\Feature;

use App\Traits\ApiResponse;
use App\Constants\HttpStatusCode;
use App\Constants\ErrorCode;
use Illuminate\Http\JsonResponse;

class TestControllerWithApiResponse
{
    use ApiResponse;

    public function callSuccessResponse($data, $message, $statusCode = null): JsonResponse
    {
        return $this->successResponse($data, $message, $statusCode ?? HttpStatusCode::OK);
    }

    public function createdResponseWrapper($data, $message = 'Created'): JsonResponse
    {
        return $this->createdResponse($data, $message);
    }

    public function errorResponseWrapper(string $message, string $code, int $statusCode, $details = null): JsonResponse
    {
        return $this->errorResponse($message, $code, $statusCode, $details);
    }

    public function validationErrorResponseWrapper(array $errors): JsonResponse
    {
        return $this->validationErrorResponse($errors);
    }

    public function notFoundResponseWrapper(string $message, string $code): JsonResponse
    {
        return $this->notFoundResponse($message, $code);
    }

    public function unauthorizedResponseWrapper(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    public function forbiddenResponseWrapper(string $message = 'Forbidden'): JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    public function conflictResponseWrapper(string $message, string $code): JsonResponse
    {
        return $this->conflictResponse($message, $code);
    }

    public function serverErrorResponseWrapper(string $message = 'Server Error'): JsonResponse
    {
        return $this->serverErrorResponse($message);
    }

    public function noContentResponseWrapper(): JsonResponse
    {
        return $this->noContentResponse();
    }

    public function paginatedResponseWrapper($paginator): JsonResponse
    {
        return $this->paginatedResponse($paginator);
    }
}

describe('ApiResponse Trait', function () {

    beforeEach(function () {
        $this->controller = new TestControllerWithApiResponse();
    });

    it('returns success response with correct structure', function () {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = $this->controller->callSuccessResponse($data, 'Success message');

        expect($response)->toBeInstanceOf(JsonResponse::class)
            ->and($response->getStatusCode())->toBe(HttpStatusCode::OK)
            ->and($response->getData(true))->toHaveKeys(['success', 'data', 'message'])
            ->and($response->getData(true)['success'])->toBeTrue()
            ->and($response->getData(true)['data'])->toBe($data)
            ->and($response->getData(true)['message'])->toBe('Success message');
    });

    it('returns created response with 201 status', function () {
        $data = ['id' => 1];
        $response = $this->controller->createdResponseWrapper($data, 'Created');

        expect($response->getStatusCode())->toBe(HttpStatusCode::CREATED)
            ->and($response->getData(true)['success'])->toBeTrue();
    });

    it('returns error response with correct structure', function () {
        $response = $this->controller->errorResponseWrapper(
            'Error occurred',
            ErrorCode::GENERAL_ERROR,
            HttpStatusCode::BAD_REQUEST
        );

        expect($response->getStatusCode())->toBe(HttpStatusCode::BAD_REQUEST)
            ->and($response->getData(true)['success'])->toBeFalse()
            ->and($response->getData(true)['error'])->toHaveKeys(['message', 'code'])
            ->and($response->getData(true)['error']['message'])->toBe('Error occurred')
            ->and($response->getData(true)['error']['code'])->toBe(ErrorCode::GENERAL_ERROR);
    });

    it('includes error details when provided', function () {
        $errors = ['field1' => ['error1', 'error2']];
        $response = $this->controller->errorResponseWrapper(
            'Validation failed',
            ErrorCode::VALIDATION_ERROR,
            HttpStatusCode::UNPROCESSABLE_ENTITY,
            $errors
        );

        expect($response->getData(true)['error'])->toHaveKey('details')
            ->and($response->getData(true)['error']['details'])->toBe($errors);
    });

    it('returns validation error response', function () {
        $errors = ['email' => ['Email is required']];
        $response = $this->controller->validationErrorResponseWrapper($errors);

        expect($response->getStatusCode())->toBe(HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->and($response->getData(true)['error']['code'])->toBe('VALIDATION_ERROR')
            ->and($response->getData(true)['error']['details'])->toBe($errors);
    });

    it('returns not found response', function () {
        $response = $this->controller->notFoundResponseWrapper(
            'Resource not found',
            ErrorCode::CUSTOMER_NOT_FOUND
        );

        expect($response->getStatusCode())->toBe(HttpStatusCode::NOT_FOUND)
            ->and($response->getData(true)['error']['code'])->toBe(ErrorCode::CUSTOMER_NOT_FOUND);
    });

    it('returns unauthorized response', function () {
        $response = $this->controller->unauthorizedResponseWrapper('Unauthorized access');

        expect($response->getStatusCode())->toBe(HttpStatusCode::UNAUTHORIZED)
            ->and($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
    });

    it('returns forbidden response', function () {
        $response = $this->controller->forbiddenResponseWrapper('Access denied');

        expect($response->getStatusCode())->toBe(HttpStatusCode::FORBIDDEN)
            ->and($response->getData(true)['error']['code'])->toBe('FORBIDDEN');
    });

    it('returns conflict response', function () {
        $response = $this->controller->conflictResponseWrapper(
            'Resource already exists',
            ErrorCode::CUSTOMER_ALREADY_EXISTS
        );

        expect($response->getStatusCode())->toBe(HttpStatusCode::CONFLICT)
            ->and($response->getData(true)['error']['code'])->toBe(ErrorCode::CUSTOMER_ALREADY_EXISTS);
    });

    it('returns server error response', function () {
        $response = $this->controller->serverErrorResponseWrapper();

        expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR)
            ->and($response->getData(true)['error']['code'])->toBe('SERVER_ERROR');
    });

    it('returns no content response', function () {
        $response = $this->controller->noContentResponseWrapper();

        expect($response->getStatusCode())->toBe(HttpStatusCode::NO_CONTENT);
    });

    it('returns paginated response with meta and links', function () {
        $items = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ]);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            10,
            2,
            1,
            ['path' => '/api/items']
        );

        $response = $this->controller->paginatedResponseWrapper($paginator);

        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(HttpStatusCode::OK)
            ->and($data)->toHaveKeys(['success', 'data', 'meta', 'links', 'message'])
            ->and($data['meta'])->toHaveKeys([
                'current_page', 'last_page', 'per_page', 'total', 'from', 'to'
            ])
            ->and($data['links'])->toHaveKeys(['first', 'last', 'prev', 'next']);
    });

    it('allows custom status codes in success response', function () {
        $response = $this->controller->successResponse(
            ['data' => 'test'],
            'Accepted',
            HttpStatusCode::ACCEPTED
        );

        expect($response->getStatusCode())->toBe(HttpStatusCode::ACCEPTED);
    });

    it('uses default success message when not provided', function () {
        $response = $this->controller->successResponse(['id' => 1]);

        expect($response->getData(true)['message'])->toBe('Operation successful');
    });
});
