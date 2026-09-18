<?php
namespace OCA\DigitalSignage\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string|null getControlToken()
 * @method void setControlToken(?string $controlToken)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method int|null getActivePresetId()
 * @method void setActivePresetId(?int $activePresetId)
 * @method int getRevision()
 * @method void setRevision(int $revision)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 * @method string|null getTimeZone()
 * @method void setTimeZone(?string $timeZone)
 * @method float|null getWeatherLatitude()
 * @method void setWeatherLatitude(?float $latitude)
 * @method float|null getWeatherLongitude()
 * @method void setWeatherLongitude(?float $longitude)
 */
class Token extends Entity {
    protected $token;
    protected $controlToken;
    protected $userId;
    protected $name;
    protected $activePresetId;
    protected $revision;
    protected $createdAt;
    protected $updatedAt;
    protected $timeZone;
    protected $weatherLatitude;
    protected $weatherLongitude;

    public function __construct() {
        $this->addType('token', 'string');
        $this->addType('controlToken', 'string');
        $this->addType('userId', 'string');
        $this->addType('name', 'string');
        $this->addType('activePresetId', 'integer');
        $this->addType('revision', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
        $this->addType('timeZone', 'string');
        $this->addType('weatherLatitude', 'float');
        $this->addType('weatherLongitude', 'float');
    }
}
