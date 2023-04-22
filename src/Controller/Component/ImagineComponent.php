<?php
declare(strict_types=1);

/**
 * Copyright 2011-2017, Florian Krämer
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Copyright 2011-2017, Florian Krämer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Burzum\Imagine\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Security;
use InvalidArgumentException;

/**
 * CakePHP Imagine Plugin
 *
 * @package Imagine.Controller.Component
 */
class ImagineComponent extends Component
{
    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'hashField' => 'hash',
        'checkHash' => true,
        'actions' => [],
    ];

    /**
     * Controller instance
     *
     * @var object
     */
    public $Controller;

    /**
     * Image processing operations taken by ImagineBehavior::processImage()
     *
     * This property is auto populated by ImagineComponent::unpackParams()
     *
     * @var array
     */
    public $operations = [];

    /**
     * Start Up
     *
     * @param \Cake\Event\Event $event Event instance
     * @return void
     */
    public function startup(Event $event): void
    {
        $Controller = $event->getSubject();
        $this->Controller = $Controller;

        if (!empty($this->_config['actions'])) {
            if (in_array($this->Controller->action, $this->_config['actions'])) {
                if ($this->_config['checkHash'] === true) {
                    $this->checkHash();
                }
                $this->unpackParams();
            }
        }
    }

    /**
     * Creates a hash based on the named params but ignores the hash field
     *
     * The hash can also be used to determine if there is already a cached version
     * of the requested image that was processed with these params. How you do that
     * is up to you.
     *
     * @throws \InvalidArgumentException
     * @return mixed String if a hash could be retrieved, false if not
     */
    public function getHash()
    {
        $mediaSalt = Configure::read('Imagine.salt');
        if (empty($mediaSalt)) {
            $message = 'Please configure Imagine.salt using Configure::write(\'Imagine.salt\', \'YOUR-SALT-VALUE\')';
            throw new InvalidArgumentException($message);
        }

        $request = $this->getController()->getRequest();
        $params = $request->getQueryParams();
        if (!empty($params)) {
            unset($params[$this->_config['hashField']]);
            ksort($params);

            return Security::hash(serialize($params) . $mediaSalt);
        }

        return false;
    }

    /**
     * Compares the hash passed within the named args with the hash calculated based
     * on the other named args and the imagine salt
     *
     * This is done to avoid that people can randomly generate tons of images by
     * just incrementing the width and height for example in the url.
     *
     * @param bool $error If set to false no 404 page will be rendered if the hash is wrong
     * @throws \Cake\Http\Exception\NotFoundException if the hash was not present
     * @return bool True if the hashes match
     */
    public function checkHash(bool $error = true): bool
    {
        $request = $this->getController()->getRequest();
        $hashField = $request->getQuery($this->_config['hashField']);
        if (empty($hashField) && $error) {
            throw new NotFoundException();
        }

        $result = $hashField === $this->getHash();

        if (!$result && $error) {
            throw new NotFoundException();
        }

        return $result;
    }

    /**
     * Unpacks the strings into arrays that were packed with ImagineHelper::pack()
     *
     * @param array $namedParams List of named params to unpack
     * @internal param array $params If empty the method tries to get them from Controller->request['named']
     * @return array Array with operation options for imagine, if none found an empty array
     */
    public function unpackParams(array $namedParams = []): array
    {
        $request = $this->getController()->getRequest();

        if (empty($namedParams)) {
            $namedParams = $request->getQueryParams();
        }

        foreach ($namedParams as $name => $params) {
            $tmpParams = explode(';', $params);
            $resultParams = [];
            foreach ($tmpParams as &$param) {
                [$key, $value] = explode('|', $param);
                $resultParams[$key] = $value;
            }

            $namedParams[$name] = $resultParams;
        }

        $this->operations = $namedParams;

        return $namedParams;
    }

    /**
     * Gets the image operations extracted from the request.
     *
     * @return array An array of image operations to perform
     */
    public function getOperations(): array
    {
        return $this->operations;
    }
}
