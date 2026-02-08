<?php

namespace App\Services\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Customer\Repositories\CustomerRepositoryInterface;
use App\Services\Customer\Requests\StoreCustomerRequest;
use App\Services\Customer\Requests\UpdateCustomerRequest;
use App\Services\Customer\Events\CustomerCreated;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * CustomerController constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Display a listing of customers.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $customers = $this->customerRepository->all();

        return response()->json([
            'success' => true,
            'data' => $customers,
            'message' => 'Customers retrieved successfully'
        ], 200);
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

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully'
        ], 201);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Customer not found',
                    'code' => 'CUSTOMER_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer retrieved successfully'
        ], 200);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Customer not found',
                    'code' => 'CUSTOMER_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully'
        ], 200);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Customer not found',
                    'code' => 'CUSTOMER_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Customer deleted successfully'
        ], 200);
    }
}
