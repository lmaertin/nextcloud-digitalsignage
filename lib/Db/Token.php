<?php
namespace OCA\DigitalSignage\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class Token extends Entity {
    protected $token;
    protected $userId;
    protected $name;
    protected $createdAt;

    public function __construct() {
        $this->addType('token', 'string');
        $this->addType('userId', 'string');
        $this->addType('name', 'string');
        $this->addType('createdAt', 'integer');
    }
}
