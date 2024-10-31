<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// load other dependencies
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory AS Faker;
use Cocur\Slugify\Slugify;
use DateTime;

// load my entities
use App\Entity\User;
use App\Entity\Article;
use App\Entity\Section;
use App\Entity\Tag;
class AppFixtures extends Fixture
{

    // construct to set up Faker and Hasher
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
        $this->faker = Faker::create("fr_FR");
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
