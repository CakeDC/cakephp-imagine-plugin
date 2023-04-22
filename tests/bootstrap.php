<?php
declare(strict_types=1);

/**
 * Bootstrap logic.
 */

use Cake\Core\Plugin;
use function Cake\Core\env as cakeEnv;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);
    throw new \Exception('Cannot find the root of the application, unable to run tests');
};

$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
$loader = require $root . '/vendor/autoload.php';

$loader->setPsr4('Cake\\', './vendor/cakephp/cakephp/src');
$loader->setPsr4('Cake\Test\\', './vendor/cakephp/cakephp/tests');
Plugin::getCollection()
      ->add(new \Burzum\Imagine\Plugin([
          'path' => dirname(dirname(__FILE__)) . DS,
      ]));

if (cakeEnv('FIXTURE_SCHEMA_METADATA')) {
	$loader = new Cake\TestSuite\Fixture\SchemaLoader();
	$loader->loadInternalFile(cakeEnv('FIXTURE_SCHEMA_METADATA'));
}
