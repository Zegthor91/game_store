#[ORM\Column(type: 'datetime_immutable')]
private ?\DateTimeImmutable $createdAt = null;

public function __construct()
{
    $this->createdAt = new \DateTimeImmutable();
}