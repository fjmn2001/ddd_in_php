<?php

interface PostRepository
{
    public function byId(PostId $id): Post;
    public function add(Post $post): Post;
}

class AggregateRoot
{
    private $recordedEvents = [];

    protected function recordApplyAndPublishThat(DomainEvent $event): void
    {
        $this->recordThat($event);
        $this->applyThat($event);
        $this->publishThat($event);
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    protected function applyThat(DomainEvent $event): void
    {
        $className = (new \ReflectionClass($event))->getShortName();
        $modifier = 'apply' . $className;

        $this->$modifier($event);
    }

    protected function publishThat(DomainEvent $event): void
    {
        DomainEventPublisher::instance()->publish($event);
        $className = (new \ReflectionClass($event))->getShortName();
    }

    public function recordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function clearEvents(): void
    {
        $this->recordedEvents = [];
    }
}

final class Post extends AggregateRoot
{
    public static function writeNewFrom(string $title, string $content): self
    {
        $postId = PostId::create();
        $post = new static($postId);

        $post->recordApplyAndPublishThat(
            new PostWastCreated($postId, $title, $content)
        );

        return $post;
    }

    public function publish(): void
    {
        $post->recordApplyAndPublishThat(
            new PostWastPublished($this->id)
        );
    }
}

final class PDOPostRepository implements PostRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function byId(PostId $id): Post
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM posts WHERE id = ?'
        );
        $stmt->execute([$id->id()]);

        $fetch = $stmt->fetch();

        return [];
    }

    public function add(Post $post)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO posts (title, content) VALUE (?, ?)'
        );
        $stmt->execute([
            $post->title(),
            $post->content()
        ]);

        $post->setId(new PostId($this->db->lastInsertId()));

        return $post;
    }
}