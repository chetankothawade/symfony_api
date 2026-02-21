<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'modules')]
#[ORM\UniqueConstraint(name: 'modules_name_unique', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'modules_uuid_unique', columns: ['uuid'])]
#[ORM\HasLifecycleCallbacks]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, options: ['fixed' => true])]
    private ?string $uuid = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $seqNo = null;

    #[ORM\Column(
        name: 'is_sub_module',
        type: Types::STRING,
        length: 1,
        options: ['default' => 'N'],
        columnDefinition: "ENUM('Y', 'N')"
    )]
    private ?string $isSubModule = 'N';

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        options: ['default' => 'active'],
        columnDefinition: "ENUM('active', 'inactive')"
    )]
    private ?string $status = 'active';

    #[ORM\Column(
        name: 'is_permission',
        type: Types::STRING,
        length: 1,
        options: ['default' => 'N'],
        columnDefinition: "ENUM('Y', 'N')"
    )]
    private ?string $isPermission = 'N';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, ModulePermission> */
    #[ORM\OneToMany(mappedBy: 'module', targetEntity: ModulePermission::class)]
    private Collection $modulePermissions;

    /** @var Collection<int, RoleModule> */
    #[ORM\OneToMany(mappedBy: 'module', targetEntity: RoleModule::class)]
    private Collection $roleModules;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->modulePermissions = new ArrayCollection();
        $this->roleModules = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSeqNo(): ?int
    {
        return $this->seqNo;
    }

    public function setSeqNo(?int $seqNo): static
    {
        $this->seqNo = $seqNo;

        return $this;
    }

    public function getIsSubModule(): ?string
    {
        return $this->isSubModule;
    }

    public function setIsSubModule(string $isSubModule): static
    {
        $this->isSubModule = $isSubModule;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsPermission(): ?string
    {
        return $this->isPermission;
    }

    public function setIsPermission(string $isPermission): static
    {
        $this->isPermission = $isPermission;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /** @return Collection<int, ModulePermission> */
    public function getModulePermissions(): Collection
    {
        return $this->modulePermissions;
    }

    public function addModulePermission(ModulePermission $modulePermission): static
    {
        if (!$this->modulePermissions->contains($modulePermission)) {
            $this->modulePermissions->add($modulePermission);
            $modulePermission->setModule($this);
        }

        return $this;
    }

    public function removeModulePermission(ModulePermission $modulePermission): static
    {
        $this->modulePermissions->removeElement($modulePermission);

        return $this;
    }

    /** @return Collection<int, RoleModule> */
    public function getRoleModules(): Collection
    {
        return $this->roleModules;
    }

    public function addRoleModule(RoleModule $roleModule): static
    {
        if (!$this->roleModules->contains($roleModule)) {
            $this->roleModules->add($roleModule);
            $roleModule->setModule($this);
        }

        return $this;
    }

    public function removeRoleModule(RoleModule $roleModule): static
    {
        $this->roleModules->removeElement($roleModule);

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable('now');
        if ($this->uuid === null || $this->uuid === '') {
            $this->uuid = self::generateUuidV4();
        }
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
