<?php

namespace App\Services\Customer\Repositories;

use App\Services\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Get all customers
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Find customer by ID
     *
     * @param int $id
     * @return Customer|null
     */
    public function find(int $id): ?Customer;

    /**
     * Create a new customer
     *
     * @param array $data
     * @return Customer
     */
    public function create(array $data): Customer;

    /**
     * Update a customer
     *
     * @param int $id
     * @param array $data
     * @return Customer|null
     */
    public function update(int $id, array $data): ?Customer;

    /**
     * Delete a customer
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find customer by email
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer;
}
