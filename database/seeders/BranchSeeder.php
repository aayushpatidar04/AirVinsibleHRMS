<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name'        => 'Mumbai Head Office',
                'code'        => 'MUM01',
                'address'     => '123 BKC, Bandra Kurla Complex',
                'city'        => 'Mumbai',
                'state'       => 'Maharashtra',
                'postal_code' => '400051',
                'country'     => 'India',
                'phone'       => '022-12345678',
                'email'       => 'mumbai@hrms.com',
                'is_active'   => true,
            ],
            [
                'name'        => 'Delhi Branch',
                'code'        => 'DEL01',
                'address'     => '456 Connaught Place',
                'city'        => 'New Delhi',
                'state'       => 'Delhi',
                'postal_code' => '110001',
                'country'     => 'India',
                'phone'       => '011-23456789',
                'email'       => 'delhi@hrms.com',
                'is_active'   => true,
            ],
            [
                'name'        => 'Bangalore Branch',
                'code'        => 'BLR01',
                'address'     => '789 Whitefield, ITPL Road',
                'city'        => 'Bangalore',
                'state'       => 'Karnataka',
                'postal_code' => '560066',
                'country'     => 'India',
                'phone'       => '080-34567890',
                'email'       => 'bangalore@hrms.com',
                'is_active'   => true,
            ],
            [
                'name'        => 'Jaipur Branch',
                'code'        => 'JAI01',
                'address'     => '321 MI Road',
                'city'        => 'Jaipur',
                'state'       => 'Rajasthan',
                'postal_code' => '302001',
                'country'     => 'India',
                'phone'       => '0141-45678901',
                'email'       => 'jaipur@hrms.com',
                'is_active'   => true,
            ],
        ];

        foreach ($branches as $data) {
            Branch::firstOrCreate(['code' => $data['code']], $data);
        }

        $this->command->info('✔ ' . count($branches) . ' branches seeded.');
    }
}