<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()

    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        \App\Models\User::truncate();
        \App\Models\Category::truncate();
        \App\Models\Product::truncate();
        \App\Models\Transaction::truncate();
        DB::table('category_product')->truncate();

        \App\Models\User::factory(1000)->create();
        \App\Models\Category::factory(30)->create();
        \App\Models\Product::factory(500)->create()->each(
            function($product) {
                $categories = \App\Models\Category::all()->random(mt_rand(1, 5))->pluck('id');
                $product->categories()->attach($categories); 
            }
        );
        \App\Models\Transaction::factory(500)->create();
    }
}
