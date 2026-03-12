<?php

namespace App\Entity;

use App\Repository\ChatRoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatRoomRepository::class)]
class ChatRoom
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?\Symfony\Component\Uid\Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    private ?Ressource $Ressource = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $members;

    /**
     * @var Collection<int, ChatMessage>
     */
    #[ORM\OneToMany(mappedBy: 'chatRoom', targetEntity: ChatMessage::class, orphanRemoval: true)]
    private Collection $messages;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'chat_room_pending_members')]
    private Collection $pendingMembers;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->pendingMembers = new ArrayCollection();
    }

    public function getId(): ?\Symfony\Component\Uid\Uuid
    {
        return $this->id;
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

    public function getRessource(): ?Ressource
    {
        return $this->Ressource;
    }

    public function setRessource(?Ressource $Ressource): static
    {
        $this->Ressource = $Ressource;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(User $member): static
    {
        $this->members->removeElement($member);

        return $this;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setChatRoom($this);
        }

        return $this;
    }

    public function removeMessage(ChatMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getChatRoom() === $this) {
                $message->setChatRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPendingMembers(): Collection
    {
        return $this->pendingMembers;
    }

    public function addPendingMember(User $pendingMember): static
    {
        if (!$this->pendingMembers->contains($pendingMember)) {
            $this->pendingMembers->add($pendingMember);
        }

        return $this;
    }

    public function removePendingMember(User $pendingMember): static
    {
        $this->pendingMembers->removeElement($pendingMember);

        return $this;
    }
}
