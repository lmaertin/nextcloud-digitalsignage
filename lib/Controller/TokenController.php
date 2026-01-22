<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;

class TokenController extends Controller {
    private $tokenMapper;
    private $userId;

    public function __construct(
        string $AppName,
        IRequest $request,
        TokenMapper $tokenMapper,
        ?string $UserId
    ) {
        parent::__construct($AppName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->userId = $UserId;
    }

    /**
     * @NoAdminRequired
     */
    public function create(string $name): JSONResponse {
        try {
            $token = bin2hex(random_bytes(32));
            
            $tokenEntity = new Token();
            $tokenEntity->setToken($token);
            $tokenEntity->setUserId($this->userId);
            $tokenEntity->setName($name);
            $tokenEntity->setCreatedAt(time());
            
            $this->tokenMapper->insert($tokenEntity);
            
            return new JSONResponse([
                'id' => $tokenEntity->getId(),
                'token' => $token,
                'name' => $name,
                'url' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token])
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function list(): JSONResponse {
        try {
            $tokens = $this->tokenMapper->findByUserId($this->userId);
            
            $result = array_map(function($token) {
                return [
                    'id' => $token->getId(),
                    'token' => substr($token->getToken(), 0, 8) . '...',
                    'name' => $token->getName(),
                    'createdAt' => $token->getCreatedAt(),
                    'url' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token->getToken()])
                ];
            }, $tokens);
            
            return new JSONResponse($result);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function delete(int $id): JSONResponse {
        try {
            $token = $this->tokenMapper->find($id);
            
            if ($token->getUserId() !== $this->userId) {
                return new JSONResponse(['error' => 'Unauthorized'], 403);
            }
            
            $this->tokenMapper->delete($token);
            
            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
}
