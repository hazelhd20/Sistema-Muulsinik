<?php

namespace App\Repositories;

use App\DTOs\ClientDTO;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ClientRepository extends BaseRepository
{
    protected string $modelClass = Client::class;

    /**
     * @param ClientDTO $dto
     * @return Client
     */
    public function save(ClientDTO $dto): Client
    {
        return $this->saveRecord([
            'name' => $dto->name,
            'legal_name' => $dto->legal_name,
            'rfc' => $dto->rfc,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'active' => $dto->active,
        ], $dto->id);
    }

    /**
     * Toggle the active status of a client
     * 
     * @param int $id
     * @return Client
     */
    public function toggleActive(int $id): Client
    {
        return DB::transaction(function () use ($id) {
            $client = Client::findOrFail($id);
            $client->update(['active' => !$client->active]);
            return $client;
        });
    }
}
