<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Исправляем created_by для клиентов, созданных массажистками.
     * created_by должен указывать на компанию, а responsible_id на создателя.
     */
    public function up(): void
    {
        // Находим клиентов, где created_by указывает на пользователя (не компанию)
        // и переносим created_by в responsible_id, а created_by меняем на creatorId пользователя
        $clients = DB::table('massage_clients')
            ->join('users', 'massage_clients.created_by', '=', 'users.id')
            ->where('users.type', '!=', 'company')
            ->select('massage_clients.id', 'massage_clients.created_by', 'users.created_by as company_id')
            ->get();

        foreach ($clients as $client) {
            DB::table('massage_clients')
                ->where('id', $client->id)
                ->update([
                    'responsible_id' => $client->created_by,
                    'created_by' => $client->company_id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откат не предусмотрен
    }
};
