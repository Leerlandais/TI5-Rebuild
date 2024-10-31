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
        $this->slugify = new Slugify();
    }

    public function load(ObjectManager $manager): void
    {

        // First off, create Mika and Myself as SUPER
        $super = new User();
        $super->setUsername("leerlandais");
        $super->setRoles("ROLE_SUPER");

        $manager->flush();
    }
}
