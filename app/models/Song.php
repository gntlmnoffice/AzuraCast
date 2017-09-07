<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="songs", indexes={
 *   @index(name="search_idx", columns={"text", "artist", "title"})
 * })
 * @Entity(repositoryClass="Entity\Repository\SongRepository")
 */
class Song
{
    const SYNC_THRESHOLD = 604800; // 604800 = 1 week

    /**
     * @Column(name="id", type="string", length=50)
     * @Id
     * @var string
     */
    protected $id;

    /**
     * @Column(name="text", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $text;

    /**
     * @Column(name="artist", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $artist;

    /**
     * @Column(name="title", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $title;

    /**
     * @Column(name="created", type="integer")
     * @var int
     */
    protected $created;

    /**
     * @Column(name="play_count", type="integer")
     * @var int
     */
    protected $play_count;

    /**
     * @Column(name="last_played", type="integer")
     * @var int
     */
    protected $last_played;

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="song")
     * @OrderBy({"timestamp" = "DESC"})
     * @var Collection
     */
    protected $history;

    /**
     * Song constructor.
     * @param array $song_info
     */
    public function __construct(array $song_info)
    {
        if (empty($song_info['text'])) {
            $song_info['text'] = $song_info['artist'] . ' - ' . $song_info['title'];
        }

        $this->text = $song_info['text'];
        $this->title = $song_info['title'];
        $this->artist = $song_info['artist'];
        $this->id = self::getSongHash($song_info);

        $this->created = time();
        $this->play_count = 0;
        $this->last_played = 0;

        $this->history = new ArrayCollection;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @return string|null
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getPlayCount(): int
    {
        return $this->play_count;
    }

    /**
     * @return int
     */
    public function getLastPlayed(): int
    {
        return $this->last_played;
    }

    /**
     * Increment the play counter and last-played items.
     */
    public function played()
    {
        $this->play_count += 1;
        $this->last_played = time();
    }

    /**
     * @return Collection
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @return Api\Song
     */
    public function api(): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->id;
        $response->text = (string)$this->text;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;

        return $response;
    }

    /**
     * @param $song_info
     * @return string
     */
    public static function getSongHash($song_info): string
    {
        // Handle various input types.
        if ($song_info instanceof self) {
            $song_info = [
                'text' => $song_info->getText(),
                'artist' => $song_info->getArtist(),
                'title' => $song_info->getTitle(),
            ];
        } elseif (!is_array($song_info)) {
            $song_info = [
                'text' => $song_info,
            ];
        }

        // Generate hash.
        if (!empty($song_info['text'])) {
            $song_text = $song_info['text'];
        } else {
            if (!empty($song_info['artist'])) {
                $song_text = $song_info['artist'] . ' - ' . $song_info['title'];
            } else {
                $song_text = $song_info['title'];
            }
        }

        // Strip non-alphanumeric characters
        $hash_base = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $song_text));

        return md5($hash_base);
    }
}