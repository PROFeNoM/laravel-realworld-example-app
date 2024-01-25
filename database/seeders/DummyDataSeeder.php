<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    protected int $totalUsers = 5;

    protected int $totalTags = 3;

    protected float $userWithArticleRatio = 0.8;

    protected int $maxArticlesByUser = 3;

    protected int $maxTagsByArticle = 3;

    protected int $maxCommentsByArticle = 3;

    protected float $usersWithFavoritesRatio = 0.75;

    protected float $usersWithFollowingRatio = 0.75;

    /**
     * Populate the database with dummy data for benchmarking.
     * Complete dummy data generation including relationships.
     */
    public function run(\Faker\Generator $faker)
    {
        $faker->seed(42);

        $this->command->info('Populating the database with dummy data...');

        $this->command->info('Creating users...');
        $users = \App\Models\User::factory()->count($this->totalUsers)->create();

        $this->command->info('Creating tags...');
        $tags = \App\Models\Tag::factory()->count($this->totalTags)->create();

        $this->command->info('Creating articles...');
        $users->random((int) $this->totalUsers * $this->userWithArticleRatio)
            ->each(function (\App\Models\User $user) use ($faker, $tags) {
                $this->command->info("Creating articles for user {$user->id}...");
                $user->articles()
                    ->saveMany(
                        \App\Models\Article::factory()
                            ->count($faker->numberBetween(1, $this->maxArticlesByUser))
                            ->make()
                    )
                    ->each(function (\App\Models\Article $article) use ($faker, $tags) {
                        $this->command->info("Creating tags and comments for article {$article->id}...");
                        $article->tags()->attach(
                            $tags->random($faker->numberBetween(1, $this->maxTagsByArticle))->pluck('id')->toArray()
                        );

                        $article->comments()->saveMany(
                            \App\Models\Comment::factory()
                                ->count($faker->numberBetween(1, $this->maxCommentsByArticle))
                                ->make()
                        );
                    });
            });

        $articles = \App\Models\Article::all();

        $users->random((int) $users->count() * $this->usersWithFavoritesRatio)
            ->each(function (\App\Models\User $user) use ($faker, $articles) {
                $articles->random($faker->numberBetween(1, (int) $articles->count() * 0.5))
                    ->each(function (\App\Models\Article $article) use ($user) {
                        $user->favorite($article);
                    });
            });

        $users->random((int) $users->count() * $this->usersWithFollowingRatio)
            ->each(function (\App\Models\User $user) use ($faker, $users) {
                $users->random($faker->numberBetween(1, (int) $users->count() * 0.5))
                    ->each(function (\App\Models\User $userToFollow) use ($user) {
                        $user->follow($userToFollow);
                    });
            });
    }
}
