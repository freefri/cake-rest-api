<?php
declare(strict_types=1);

namespace RestApi\Controller;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\NotImplementedException;
use Cake\Http\Response;
use Cake\ORM\Entity;
use Cake\View\JsonView;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\StreamInterface;
use RestApi\Controller\Component\ApiRestCorsComponent;
use RestApi\Lib\Exception\SilentException;
use RestApi\Lib\RestRenderer;

abstract class RestApiController extends Controller
{
    const STORE = 'addNew';
    const INDEX = 'getList';
    const SHOW = 'getData';
    const UPDATE = 'edit';
    const PUT = 'put';
    const DESTROY = 'delete';

    private static $_hasSwagger = [];

    protected $return;
    protected $flatResponse = false;
    protected $useOauthServer = true;

    protected function loadComponentFromClass(string $className)
    {
        $arraySplit = explode('\\', $className);
        $AppCorsClassName = explode('Component', array_pop($arraySplit))[0];
        $this->loadComponent($AppCorsClassName);
    }

    public function isPublicController(): bool
    {
        return false;
    }

    protected function defineMainEntity(): ?Entity
    {
        return null;
    }

    public function getMainEntity(): ?Entity
    {
        if (self::$_hasSwagger[static::cls()] ?? false) {
            return $this->defineMainEntity();
        }
        return null;
    }

    public function docMethodParams(): array
    {
        return [];
    }

    protected function getMandatoryParams(): array
    {
        return $this->getRequest()->getAttribute('route')->keys;
    }

    protected function setPublicAccess()
    {
        $this->useOauthServer = false;
    }

    private static function cls(): string
    {
        $className = namespaceSplit(static::class);
        return substr(array_pop($className), 0, -1 * strlen('Controller'));
    }

    public final static function route(): array
    {
        return ['controller' => static::cls(), 'action' => 'main'];
    }

    public function initialize(): void
    {
        if ($this->isPublicController()) {
            $this->setPublicAccess();
        }
        parent::initialize();
        //$this->loadModel('Users');
        //$this->loadModel('Events');

        $this->_loadCorsComponent();
        if ($this->useOauthServer) {
            $this->_loadOAuthServerComponent();
        }
        $this->_setUserLang();
    }

    protected abstract function _loadCorsComponent(): ApiRestCorsComponent;
    protected abstract function _loadOAuthServerComponent(): Component;

    protected abstract function _setUserLang(): void;

    public function beforeFilter(EventInterface $event)
    {
        foreach ($this->getMandatoryParams() as $param) {
            if (!$this->request->is('OPTIONS') && $this->getRequest()->getParam($param) < 1) {
                throw new BadRequestException('Invalid mandatory params in URL: '.$param.' in '.$this->getName());
            }
        }
        $this->_setLanguage();
        parent::beforeFilter($event);
    }

    protected abstract function _setLanguage(): void;

    public function main($id = null, $secondParam = null)
    {
        $this->_setLanguage();
        $bypass = $this->beforeMain($id, $secondParam);
        if ($bypass) {
            return null;
        }
        $this->_main($id, $secondParam);
    }

    protected function beforeMain($id = null, $secondParam = null)
    {
        return null;
    }

    private function _main($id = null, $secondParam = null): Response
    {
        if ($this->request->getParam('eventID') && $this->request->getParam('userID')) {
            if (!$this->Event->doesOwnEvent($this->request->getParam('eventID'), $this->request->getParam('userID'))) {
                throw new ForbiddenException('Event does not belong to seller');
            }
        }
        if ($secondParam !== null) {
            throw new NotFoundException('Invalid resource locator');
        }
        if ($id === null || $id === '') {
            if ($this->request->is('GET')) {
                $this->getList();
            } elseif ($this->request->is('POST')) {
                $this->addNew($this->_getNoEmptyData());
                if ($this->return === false) {
                    $this->response = $this->response->withStatus(204);
                    $this->autoRender = false;
                    return $this->response;
                }
                if (is_array($this->return)) {
                    $this->response->withStatus(201);
                }
            } else {
                throw new MethodNotAllowedException('HTTP method requires ID');
            }
        } else {
            if (!$id) {
                throw new ForbiddenException('Not valid resource id');
            }
            if ($this->request->is('GET')) {
                $this->getData($id);
            } elseif ($this->request->is('PATCH')) {
                $this->edit($id, $this->_getNoEmptyData());
            } elseif ($this->request->is('PUT')) {
                $this->put($id, $this->_getNoEmptyData());
            } elseif ($this->request->is('DELETE')) {
                $this->delete($id);
                if ($this->return === false) {
                    $this->response = $this->response->withStatus(204);
                    $this->autoRender = false;
                    return $this->response;
                }
            } elseif ($this->request->is('HEAD')) {
                throw new SilentException('Method HEAD: not allowed ' . json_encode($this->_getSecuredServer()), 400);
            } else {
                throw new MethodNotAllowedException('MethodNotAllowed ' . json_encode($this->_getSecuredServer()));
            }
        }
        return $this->response;
    }

    private function _getSecuredServer()
    {
        $server['AUTH_TOKEN_UID'] = $_SERVER['AUTH_TOKEN_UID'] ?? '';
        $server['TAG_VERSION'] = $_SERVER['TAG_VERSION'] ?? '';
        $server['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? '';
        $server['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '';
        $server['APPLICATION_ENV'] = $_SERVER['APPLICATION_ENV'] ?? '';
        $server['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $server['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '';
        $server['HTTP_ORIGIN'] = $_SERVER['HTTP_ORIGIN'] ?? '';
        $server['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'] ?? '';
        $server['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $server['QUERY_STRING'] = $_SERVER['QUERY_STRING'] ?? '';
        $server['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? '';
        return $server;
    }

    private function _getNoEmptyData()
    {
        $data = $this->request->getData();
        if (!$data) {
            $data = $this->_parseInput($this->request->getBody());
            if ($data) {
                return $data;
            }
            throw new BadRequestException('Empty body or invalid Content-Type in HTTP request');
        }
        return $data;
    }

    private function _parseInput(StreamInterface $stream)
    {
        $stream->rewind();
        return $stream->getContents();
    }

    public function beforeRender(EventInterface $event)
    {
        if ($this->return instanceof RestRenderer) {
            $ret = $this->return;
            $this->autoRender = false;
            $this->response = $ret->setHeadersForDownload($this->response);
            $this->response = $this->response->withStringBody($ret->render());
            return $this->response;
        } else if ($this->return || $this->return === []) {
            $isOneEntity = $this->return instanceof Entity
                || (count($this->return) == 1 && !$this->return instanceof ResultSetInterface);
            if ($isOneEntity && isset($this->return['meta'])) {
                $meta = $this->return['meta'];
                $this->set(compact('meta'));
                $this->viewBuilder()->setOption('serialize', ['meta']);
            } else {
                $data = $this->return;
                if ($this->flatResponse) {
                    $vars = [];
                    foreach ($data as $k => $d) {
                        $vars[] = $k;
                        $this->set($k, $d);
                    }
                    $this->viewBuilder()->setOption('serialize', $vars);
                } else {
                    $this->set(compact('data'));
                    $this->viewBuilder()->setOption('serialize', ['data']);
                }
            }
        }
    }

    public function render(?string $template = null, ?string $layout = null): Response
    {
        $builder = $this->viewBuilder();
        $builder->setClassName(JsonView::class);
        return parent::render($template, $layout);
    }

    protected function getList()
    {
        throw new NotImplementedException('GET list not implemented yet');
    }

    protected function addNew($data)
    {
        throw new NotImplementedException('POST resource not implemented yet');
    }

    protected function getData($id)
    {
        throw new NotImplementedException('GET resource not implemented yet');
    }

    protected function edit($id, $data)
    {
        throw new NotImplementedException('PATCH not implemented yet');
    }

    protected function put($id, $data)
    {
        throw new NotImplementedException('PUT not implemented yet');
    }

    protected function delete($id)
    {
        throw new NotImplementedException('DELETE not implemented yet');
    }

    protected function here()
    {
        return explode('?', $this->request->getRequestTarget())[0];
    }

    protected abstract function getLocalOauth();

    protected function isOwnProvider($userId): bool
    {
        try {
            $uid = $this->getLocalOauth()->verifyAuthorization();
            if ($this->getLocalOauth()->isManagerUser()) {
                return true;
            }
            return $userId == $uid;
        } catch (Exception $e) {
            return false;
        }
    }

    private function _sanitizeFilename($filename): string
    {
        $exploded = explode('.', $filename);
        $extension = array_pop($exploded);
        $filename = implode('.', $exploded);
        return $this->_sanitize($filename) . '.' . $this->_sanitize($extension);
    }

    private function _sanitize($filename): string
    {
        $match = array("/\s+/", "/[^a-zA-Z0-9\-]/", "/-+/", "/^-+/", "/-+$/");
        $replace = array("-", "", "-", "", "");
        $string = preg_replace($match, $replace, $filename);
        return strtolower($string);
    }

    protected function getFileInfo(): array
    {
        $uploadedTestFiles = $this->request->getUploadedFiles();
        /** @var UploadedFile $file */
        $file = $uploadedTestFiles['file'] ?? null;
        if (!$file) {
            throw new BadRequestException('File must be provided');
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
                throw new InternalErrorException('upload failed, max ' . ini_get('upload_max_filesize') . 'B', 500);
            }
            throw new InternalErrorException('Error uploading file, code: ' . $file['error'] ?? '');
        }

        $name = $this->_sanitizeFilename($file->getClientFilename());
        $tmpName = TMP . $name;
        $file->moveTo($tmpName);
        return [
            'name' => $name,
            'type' => $file->getClientMediaType(),
            'tmp_name' => $tmpName,
            'error' => $file->getError(),
            'size' => $file->getSize(),
        ];
    }
}
