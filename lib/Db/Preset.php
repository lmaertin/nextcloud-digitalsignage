<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getImageFolder()
 * @method void setImageFolder(string $imageFolder)
 * @method string getImageFitMode()
 * @method void setImageFitMode(string $imageFitMode)
 * @method string getImageOrderMode()
 * @method void setImageOrderMode(string $imageOrderMode)
 * @method string getFullscreenSlideshow()
 * @method void setFullscreenSlideshow(string $fullscreenSlideshow)
 * @method string getShowDisplayName()
 * @method void setShowDisplayName(string $showDisplayName)
 * @method int getSlideInterval()
 * @method void setSlideInterval(int $slideInterval)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Preset extends Entity {
    protected $userId;
    protected $name;
    protected $imageFolder;
    protected $imageFitMode;
    protected $imageOrderMode;
    protected $fullscreenSlideshow;
    protected $showDisplayName;
    protected $slideInterval;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('name', 'string');
        $this->addType('imageFolder', 'string');
        $this->addType('imageFitMode', 'string');
        $this->addType('imageOrderMode', 'string');
        $this->addType('fullscreenSlideshow', 'string');
        $this->addType('showDisplayName', 'string');
        $this->addType('slideInterval', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }
}
