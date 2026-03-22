<?php
namespace App\Models;

class TenantModel {
    /**
     * Retrieve all tenant records
     * Eventually, this will use fgetcsv() or a DB query
     */
    public function getAllTenants(): array {
        return [
            [
                "id" => 101,
                "name" => "John Wick",
                "property_name" => "Continental Suite 01",
                "status" => "active",
                "balance" => 0.00
            ],
            [
                "id" => 102,
                "name" => "Sarah Connor",
                "property_name" => "Cyberdyne Lofts",
                "status" => "late",
                "balance" => -1250.50
            ],
            [
                "id" => 103,
                "name" => "Deckard Shaw",
                "property_name" => "Industrial Park A",
                "status" => "active",
                "balance" => 450.00
            ]
        ];
    }
}