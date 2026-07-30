<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a user can register and login', function () {
    $this->postJson('/api/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
});

test('inventory endpoints require authentication', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

test('products can be created, searched, paginated, updated, and deleted', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::factory()->create(['name' => 'Computers']);
    $supplier = Supplier::factory()->create(['company_name' => 'Tech Supply Co']);
    Product::factory()->create(['name' => 'Office Chair']);

    $productId = $this->postJson('/api/products', [
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'name' => 'Laptop Pro 15',
        'price' => 1299.99,
        'quantity' => 8,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Laptop Pro 15')
        ->assertJsonPath('data.category.name', 'Computers')
        ->json('data.id');

    $this->getJson('/api/products?search=laptop&per_page=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Laptop Pro 15')
        ->assertJsonPath('meta.per_page', 1);

    $this->putJson("/api/products/{$productId}", [
        'quantity' => 10,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.quantity', 10);

    $this->deleteJson("/api/products/{$productId}")
        ->assertNoContent();

    expect(Product::find($productId))->toBeNull();
});

test('categories and suppliers can be created and listed', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/categories', ['name' => 'Accessories'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Accessories');

    $this->postJson('/api/suppliers', [
        'company_name' => 'Warehouse One',
        'email' => 'sales@warehouse.test',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.company_name', 'Warehouse One');

    $this->getJson('/api/categories')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Accessories']);

    $this->getJson('/api/suppliers')
        ->assertSuccessful()
        ->assertJsonFragment(['company_name' => 'Warehouse One']);
});

test('stock can move in and out without going negative', function () {
    Sanctum::actingAs(User::factory()->create());

    $product = Product::factory()->create(['quantity' => 5]);

    $this->postJson("/api/products/{$product->id}/stock-in", ['quantity' => 4])
        ->assertSuccessful()
        ->assertJsonPath('data.quantity', 9);

    $this->postJson("/api/products/{$product->id}/stock-out", ['quantity' => 3])
        ->assertSuccessful()
        ->assertJsonPath('data.quantity', 6);

    $this->postJson("/api/products/{$product->id}/stock-out", ['quantity' => 7])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});
