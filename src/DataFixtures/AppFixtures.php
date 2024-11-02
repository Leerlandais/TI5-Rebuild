<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// load other dependencies
use phpDocumentor\Reflection\Utils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory AS Faker;
use Cocur\Slugify\Slugify;
use DateTime;

// load my entities
use App\Entity\User;
use App\Entity\Article;
use App\Entity\Section;
use App\Entity\Tag;
use App\Entity\Comment;
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

    private function createQuote()
    {
        return $this->faker->realText(mt_rand(15, 100));
    }

    private function createPubDate($maxDate): DateTime
    {
        return $this->faker->dateTimeBetween($maxDate,(clone $maxDate)->modify('+2 months'));
    }

    public function load(ObjectManager $manager): void
    {
        $admins = [];
        $users = [];
        $articles = [];
        $sections = [];

        // First off, create Mika and Myself as SUPER
        $super = new User();
        $super->setUsername("leerlandais");
        $super->setRoles(["ROLE_SUPER"]);
        $super->setPassword($this->hasher->hashPassword($super, "270675"));
        $super->setFullname("Lee Brennan");
        $super->setUniqid(uniqid('user_', true));
        $super->setEmail("lee@leerlandais.com");
        $super->setActivate(true);
        $super->setImgLoc("../assets/img/lee.jpg");
        $super->setQuote("Eat, Code, Sleep, Repeat");

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
        $super->setImgLoc("../assets/img/mjp.jpg");
        $super->setQuote("En Gelbique");

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
        $admin->setQuote($this->createQuote());

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
            $redac->setQuote($this->createQuote());

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
            $user->setQuote($this->createQuote());

            $this->users[] = $user;
            $manager->persist($user);
        }

        $manager->flush(); // turns out multiple flushes are permitted in on load

        // Now, create the articles
        for ($i = 0; $i < 160; $i++) {
            $article = new Article();
            $randUser = array_rand($this->admins);
            $article->setUser($this->admins[$randUser]);
            $article->setTitle($this->createTitle(mt_rand(10,40)));
            $article->setTitleSlug($this->slugify->slugify($article->getTitle()));
            $article->setText($this->createText(mt_rand(4,10)));
            $date = $this->faker->dateTimeThisDecade();
            $article->setArticleDateCreated($date);
            $isPub = mt_rand(0, 4);
            $article->setPublished($isPub);
            if ($isPub) {
                $datePub = $this->createPubDate($date);
                $article->setArticleDatePosted($datePub);
            }

            $this->articles[] = $article;
            $manager->persist($article);
        }
            $artLength = count($this->articles); // decided to adjust the amount of sections, tags, etc dynamically by the amount of articles


        $manager->flush();

        $sectionCount = min(($artLength / 10), 6); // Some more PHPStorm, it replaced $sectionCount = ($artLength / 10) > 6 ? 6 : ($artLength / 10);
            $arts = $this->articles;

        for ($i = 0; $i < $sectionCount; $i++) {
            $section = new Section();
            $section->setSectionTitle($this->createTitle(mt_rand(10, 15)));
            $section->setSectionSlug($this->slugify->slugify($section->getSectionTitle()));
            $section->setSectionDetail($this->createText(mt_rand(1,1)));

            $this->sections[] = $section;

            shuffle($arts); // I could copy the array and shuffle that, leaving the original array alone...

            $nbArt = mt_rand(($artLength / 8), ($artLength / 4));

            $randArts = array_slice($arts, 0, $nbArt);
            foreach ($randArts as $art) {
                $section->addArticle($art);
            }

            $manager->persist($section);
        }

        if (($artLength / 4) < 25) {
            $tagCount = 25;
        }else {
            $tagCount = $artLength / 4;
        }

        shuffle($arts); // ...or I could shuffle it again :)


        for ($i = 0; $i < $tagCount; $i++) {
            $tag = new Tag();
            $tag->setTagName($this->faker->word());
            $tag->setTagSlug($this->slugify->slugify($tag->getTagName())); // as tags are a single word, it's, maybe unnecessary to slug them but to ensure no possible error, I'll do it anyway

            $this->tags[] = $tag;

            $nbArt = mt_rand(($artLength / 4), ($artLength / 2));
            $randArts = array_slice($arts, 0, $nbArt);
            foreach ($randArts as $art) {
                $tag->addArticle($art);
            }

            $manager->persist($tag);
        }

        for ($i = 0; $i < $artLength / 2; $i++) {
            $comment = new Comment();
            $randArt = array_rand($this->articles);
            $artDate = $this->articles[$randArt]->getArticleDateCreated();
            $comment->setCommentDateCreated($this->createPubDate($artDate));
            $comment->setVisible(true);
            // dd($randArt);
            $comment->setArticle($this->articles[$randArt]);
            $randUser = array_rand($this->users);
            $comment->setUserId($randUser);
            $commentLen = mt_rand(100, 500);
            $comment->setCommentText($this->createText($commentLen));
            $manager->persist($comment);
        }


        $manager->flush();
    }
}


