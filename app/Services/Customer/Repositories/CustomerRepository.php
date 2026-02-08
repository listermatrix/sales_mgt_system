<?php

namespace App\Services\Customer\Repositories;

use App\Services\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var Customer
     */
    protected Customer $model;

    /**
     * CustomerRepository constructor.
     *
     * @param Customer $model
     */
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    /**
     * Get all customers
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->latest()->get();
    }

    /**
     * Find customer by ID
     *
     * @param int $id
     * @return Customer|null
     */
    public function find(int $id): ?Customer
    {
        return $this->model->find($id);
    }

    /**
     * Create a new customer
     *
     * @param array $data
     * @return Customer
     */
    public function create(array $data): Customer
    {
        return $this->model->create($data);
    }

    /**
     * Update a customer
     *
     * @param int $id
     * @param array $data
     * @return Customer|null
     */
    public function update(int $id, array $data): ?Customer
    {
        $customer = $this->find($id);

        if ($customer) {
            $customer->update($data);
            return $customer->fresh();
        }

        return null;
    }

    /**
     * Delete a customer
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $customer = $this->find($id);

        if ($customer) {
            return $customer->delete();
        }

        return false;
    }

    /**
     * Find customer by email
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }
}
