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
        $this->faker = Faker::create("en_GB");
        $this->slugify = new Slugify();
    }

    private function createTitle($nb)
    {
        return $this->faker->realText($nb);

    }

    private function createText($nb)
    {
        $paragraphs = [];
        for ($i = 0; $i < $nb; $i++) {
            $paragraphs[] = $this->faker->realText(mt_rand(150, 300));
        }
        return implode("\n\n", $paragraphs);

    }

    private function createImage()
    {

        $sex = mt_rand(0, 1) ? "men" : "women";
        $img = mt_rand(1, 99);
        return ("https://randomuser.me/api/portraits/$sex/$img.jpg");
    }

    public function load(ObjectManager $manager): void
    {
        $admins = [];
        $users = [];
        $articles = [];

        // First off, create Mika and Myself as SUPER
        $super = new User();
        $super->setUsername("leerlandais");
        $super->setRoles(["ROLE_SUPER"]);
        $super->setPassword($this->hasher->hashPassword($super, "270675"));
        $super->setFullname("Lee Brennan");
        $super->setUniqid(uniqid('user_', true));
        $super->setEmail("lee@leerlandais.com");
        $super->setActivate(true);
        $super->setImgLoc($this->createImage());

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
        $super->setImgLoc($this->createImage());

        $this->admins[] = $super;
        $manager->persist($super);

        // then the admin user
        $admin = new User();
        $admin->setUsername("admin");
        $admin->setRoles(["ROLE_ADMIN"]);
        $admin->setPassword($this->hasher->hashPassword($admin, "admin"));
        $admin->setFullname($this->faker->name());
        $admin->setUniqid(uniqid('user_', true));
        $admin->setEmail($this->faker->email());
        $admin->setActivate(true);
        $admin->setImgLoc($this->createImage());

        $this->admins[] = $admin;
        $manager->persist($admin);

        // and the redac users
        for ($i = 1; $i < 6; $i++) {
            $redac = new User();
            $redac->setUsername("redac".$i);
            $redac->setRoles(["ROLE_REDAC"]);
            $redac->setPassword($this->hasher->hashPassword($redac, "redac".$i));
            $redac->setFullname($this->faker->name());
            $redac->setUniqid(uniqid('user_', true));
            $redac->setEmail($this->faker->email());
            $redac->setActivate(true);
            $redac->setImgLoc($this->createImage());

            $this->admins[] = $redac;
            $manager->persist($redac);
        }

        // and finally the entry level users
        for ($i = 1; $i < 25; $i++) {
            $user = new User();
            $user->setUsername("user".$i);
            $user->setRoles(["ROLE_USER"]);
            $user->setPassword($this->hasher->hashPassword($user, "user".$i));
            $user->setFullname($this->faker->name());
            $user->setUniqid(uniqid('user_', true));
            $user->setEmail($this->faker->email());
            $user->setActivate(mt_rand(0, 3));
            $user->setImgLoc($this->createImage());

            $this->users[] = $user;
            $manager->persist($user);
        }

        // Now, create the articles
        for ($i = 0; $i < 160; $i++) {
            $article = new Article();
            $randUser = array_rand($this->admins);
            $article->setUser($this->admins[$randUser]);
            $article->setTitle($this->createTitle(mt_rand(10,40)));
            $article->setTitleSlug($this->slugify->slugify($article->getTitle()));
            $article->setText($this->createText(mt_rand(4,10)));
            $article->setArticleDateCreated($this->faker->dateTime());
            $article->setArticleDatePosted($this->faker->dateTime());
            $article->setPublished(true);

            $this->articles[] = $article;
            $manager->persist($article);
        }

        $manager->flush();
    }
}

