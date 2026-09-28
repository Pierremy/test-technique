<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Clavier mécanique',
            'sku' => 'KEY-00001',
            'price' => 89.9,
            'stock' => 12,
        ], $overrides);
    }

    // --- Liste -------------------------------------------------------------

    public function test_it_lists_products_with_pagination(): void
    {
        Product::factory()->count(20)->create();

        $this->getJson('/api/products?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sku', 'price', 'stock', 'category_id', 'category' => ['id', 'name']]],
                'links',
                'meta',
            ]);
    }

    public function test_it_searches_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Chaise de bureau']);
        Product::factory()->create(['name' => 'Table basse']);

        $this->getJson('/api/products?search=chaise')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chaise de bureau');
    }

    public function test_it_filters_products_by_category_price_and_stock(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['price' => 50, 'stock' => 3]);
        Product::factory()->for($category)->outOfStock()->create(['price' => 50]);
        Product::factory()->for($category)->create(['price' => 500, 'stock' => 3]);
        Product::factory()->create(['price' => 50, 'stock' => 3]);

        $this->getJson("/api/products?category_id={$category->id}&max_price=100&in_stock=1")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_sorts_products(): void
    {
        Product::factory()->create(['price' => 20.5]);
        Product::factory()->create(['price' => 10.5]);
        Product::factory()->create(['price' => 30.5]);

        $this->getJson('/api/products?sort=price&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.price', 10.5)
            ->assertJsonPath('data.2.price', 30.5);
    }

    public function test_it_rejects_invalid_list_parameters(): void
    {
        $this->getJson('/api/products?sort=password&per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'per_page']);
    }

    // --- Détail ------------------------------------------------------------

    public function test_it_shows_a_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku)
            ->assertJsonPath('data.category.id', $product->category_id);
    }

    public function test_it_returns_404_for_an_unknown_product(): void
    {
        $this->getJson('/api/products/999')->assertNotFound();
    }

    // --- Création ----------------------------------------------------------

    public function test_it_creates_a_product(): void
    {
        $payload = $this->validPayload(['sku' => ' key-00001 ']);

        $this->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Clavier mécanique')
            ->assertJsonPath('data.sku', 'KEY-00001')
            ->assertJsonPath('data.price', 89.9);

        $this->assertDatabaseHas('products', ['sku' => 'KEY-00001', 'stock' => 12]);
    }

    public function test_it_validates_required_fields_on_creation(): void
    {
        $this->postJson('/api/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'sku', 'price', 'stock']);
    }

    public function test_it_rejects_invalid_values_on_creation(): void
    {
        $payload = $this->validPayload([
            'category_id' => 999,
            'price' => -1,
            'stock' => 1.5,
            'sku' => 'SKU AVEC ESPACES',
        ]);

        $this->postJson('/api/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'price', 'stock', 'sku']);
    }

    public function test_it_rejects_a_duplicate_sku_regardless_of_case(): void
    {
        Product::factory()->create(['sku' => 'KEY-00001']);

        $this->postJson('/api/products', $this->validPayload(['sku' => 'key-00001']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku' => 'Ce SKU est déjà utilisé par un autre produit.']);
    }

    // --- Mise à jour -------------------------------------------------------

    public function test_it_fully_updates_a_product_keeping_its_own_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'KEY-00001']);

        $this->putJson("/api/products/{$product->id}", $this->validPayload(['name' => 'Nouveau nom']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.sku', 'KEY-00001');
    }

    public function test_it_partially_updates_a_product(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->patchJson("/api/products/{$product->id}", ['stock' => 42])
            ->assertOk()
            ->assertJsonPath('data.stock', 42)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_it_rejects_an_update_with_the_sku_of_another_product(): void
    {
        Product::factory()->create(['sku' => 'TAKEN-001']);
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['sku' => 'TAKEN-001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    // --- Suppression -------------------------------------------------------

    public function test_it_deletes_a_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

        $this->assertModelMissing($product);
    }
}
