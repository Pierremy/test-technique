<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_categories_sorted_by_name_with_product_count(): void
    {
        $sport = Category::factory()->create(['name' => 'Sport']);
        Category::factory()->create(['name' => 'Livres']);
        Product::factory()->count(2)->for($sport)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Livres')
            ->assertJsonPath('data.0.products_count', 0)
            ->assertJsonPath('data.1.name', 'Sport')
            ->assertJsonPath('data.1.products_count', 2);
    }
}
