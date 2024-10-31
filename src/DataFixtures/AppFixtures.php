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

        $admins = [];

        // First off, create Mika and Myself as SUPER
        $super = new User();
        $super->setUsername("leerlandais");
        $super->setRoles(["ROLE_SUPER"]);
        $super->setPassword($this->hasher->hashPassword($super, "270675"));
        $super->setFullname("Lee Brennan");
        $super->setUniqid(uniqid('user_', true));
        $super->setEmail("lee@leerlandais.com");
        $super->setActivate(true);

        $this->admins[] = $super;
        $manager->persist($super);

        $super = new User();
        $super->setUsername("mika");
        $super->setRoles(["ROLE_SUPER"]);
        $super->setPassword($this->hasher->hashPassword($super, "mjk77"));
        $super->setFullname("Michael Pitz");
        $super->setUniqid(uniqid('user_', true));
        $super->setEmail("michael.pitz@cf2m.be");
        $super->setActivate(true);

        $this->admins[] = $super;
        $manager->persist($super);

        for ($i = 0; $i < 5; $i++) {
            $admin = new User();
            $admin->setUsername("admin".$i);
            $admin->setRoles(["ROLE_ADMIN"]);
            $admin->setPassword($this->hasher->hashPassword($super, "admin".$i));
            $admin->setFullname($this->faker->name());
            $admin->setUniqid(uniqid('user_', true));
            $admin->setEmail($this->faker->email());
            $admin->setActivate(true);

            $this->admins[] = $admin;
            $manager->persist($admin);
        }




        $manager->flush();
    }
}
