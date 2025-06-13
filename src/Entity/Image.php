<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    public function getId(): ?int { return $this->id; }

    public function getUrl(): ?string { return $this->url; }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getProperty(): ?Property { return $this->property; }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
        return $this;
    }
}
