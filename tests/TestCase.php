<?php

declare(strict_types=1);

namespace Telkins\Dag\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Telkins\Dag\Providers\DagServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
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
            'driver'   => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix'   => '',
        ]);
    }

    public function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }

    protected function setUpDatabase(Application $app): void
    {
        $this->resetDatabase();
        $this->createDagEdgesTable();
        $this->createTestModelTable($app);
    }

    protected function resetDatabase(): void
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);
    }

    protected function createDagEdgesTable(): void
    {
        include_once __DIR__ . '/../database/migrations/create_dag_edges_table.php.stub';

        (new \CreateDagEdgesTable())->up();
    }

    protected function createTestModelTable(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
