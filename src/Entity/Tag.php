<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $tag_name = null;

    #[ORM\Column(length: 72)]
    private ?string $tag_slug = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTagName(): ?string
    {
        return $this->tag_name;
    }

    public function setTagName(string $tag_name): static
    {
        $this->tag_name = $tag_name;

        return $this;
    }

    public function getTagSlug(): ?string
    {
        return $this->tag_slug;
    }

    public function setTagSlug(string $tag_slug): static
    {
        $this->tag_slug = $tag_slug;

        return $this;
    }
}
