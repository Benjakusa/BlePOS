<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999);

        return [
            'name' => $name,
            'trading_name' => $name,
            'code' => strtoupper(Str::random(6)),
            'slug' => $slug,
            'kra_pin' => 'P'.fake()->unique()->numerify('#########').'X',
            'business_type' => 'retail',
            'industry' => 'general_retail',
            'status' => 'active',
            'owner_name' => fake()->name(),
            'owner_email' => fake()->unique()->safeEmail(),
            'owner_phone' => '+2547'.fake()->numerify('########'),
            'country' => 'KEN',
            'county' => 'Nairobi',
            'city' => 'Nairobi',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
        ];
    }
}
