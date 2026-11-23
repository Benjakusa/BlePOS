<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        $name = fake()->city().' Branch';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'code' => strtoupper(Str::random(6)),
            'phone' => '+2547'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'county' => 'Nairobi',
            'city' => 'Nairobi',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ];
    }
}
