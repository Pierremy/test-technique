<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = collect([
            'Électronique',
            'Informatique',
            'Maison',
            'Jardin',
            'Sport',
            'Livres',
            'Vêtements',
            'Jouets',
        ])->map(fn (string $name) => Category::create(['name' => $name]));

        Product::factory(60)
            ->recycle($categories)
            ->create();
    }
}
