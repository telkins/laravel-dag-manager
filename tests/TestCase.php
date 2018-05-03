<?php

namespace Telkins\Dag\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Telkins\Dag\Providers\DagServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [DagServiceProvider::class];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix' => '',
        ]);
    }

    public function getTempDirectory() : string
    {
        return __DIR__ . '/temp';
    }

    protected function setUpDatabase(Application $app)
    {
        $this->resetDatabase();
        $this->createDagEdgesTable();
    }

    protected function resetDatabase()
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);
    }

    protected function createDagEdgesTable()
    {
        include_once __DIR__ . '/../database/migrations/create_dag_edges_table.php.stub';

        (new \CreateDagEdgesTable())->up();
    }
}
