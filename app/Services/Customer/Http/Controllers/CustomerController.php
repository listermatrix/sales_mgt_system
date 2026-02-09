<?php

namespace App\Services\Customer\Http\Controllers;



use App\Http\Controllers\Controller;
use App\Services\Customer\Http\Resources\CustomerResource;
use App\Services\Customer\Repositories\CustomerRepositoryInterface;
use App\Services\Customer\Http\Requests\StoreCustomerRequest;
use App\Services\Customer\Http\Requests\UpdateCustomerRequest;
use App\Services\Customer\Events\CustomerCreated;
use App\Traits\ApiResponse;
use App\Constants\ErrorCode;
use Illuminate\Http\JsonResponse;

/**
 * Customer Controller
 *
 * Handles customer CRUD operations with API resources
 */
class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * CustomerController constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    )
    {
    }

    /**
     * Display a listing of customers.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $customers = $this->customerRepository->all();

        return $this->successResponse(
            CustomerResource::collection($customers),
            'Customers retrieved successfully'
        );
    }

    /**
     * Store a newly created customer.
     *
     * @param StoreCustomerRequest $request
     * @return JsonResponse
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerRepository->create($request->validated());

        // Dispatch customer created event
        event(new CustomerCreated($customer));

        return $this->createdResponse(
            new CustomerResource($customer),
            'Customer created successfully'
        );
    }

    /**
     * Display the specified customer.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if (!$customer) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::CUSTOMER_NOT_FOUND),
                ErrorCode::CUSTOMER_NOT_FOUND
            );
        }

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer retrieved successfully'
        );
    }

    /**
     * Update the specified customer.
     *
     * @param UpdateCustomerRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->customerRepository->update($id, $request->validated());

        if (!$customer) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::CUSTOMER_NOT_FOUND),
                ErrorCode::CUSTOMER_NOT_FOUND
            );
        }

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer updated successfully'
        );
    }

    /**
     * Remove the specified customer.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->customerRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::CUSTOMER_NOT_FOUND),
                ErrorCode::CUSTOMER_NOT_FOUND
            );
        }

        return $this->successResponse(
            null,
            'Customer deleted successfully'
        );
    }
}
