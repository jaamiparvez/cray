<?php

namespace JunaidQadirB\Cray\Console\Commands;

use Illuminate\Support\Str;
use JunaidQadirB\Cray\Console\Contracts\GeneratorCommand;

class Cray extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cray';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cray
    {name : Model name. Controller, factory, migration, views will be based on this name.}
    {--views-dir= : Place views in a sub-directory under the views directory. It can be any nested directory structure}
    {--controller-dir= : Place controller in a sub-directory under the Http/Controllers directory. It can be any nested directory structure}
    {--route-base= : Base name for the route. Example: dashboard.analytics}
    {--stubs-dir= : Specify a custom stubs directory}
    {--no-views : Do not create view files for the model}
    {--no-migration : Do not create a migration for the model}
    {--no-factory : Do not create a factory for the model}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a nearly complete boilerplate CRUD pages for the specified Model';

    /**
     * Create a new command instance.
     *
     */
    /*    public function __construct()
        {
            parent::__construct();
        }*/

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * Steps
         * - Generate Model
         * - Generate Factory
         * - Generate Migration
         * - Generate Controller
         * - Generate Requests
         * - Generate Views
         *
         */
        $this->type = 'Model';

        if (!$this->option('no-factory')) {
            $this->createFactory();
        }

        if (!$this->option('no-migration')) {
            $this->createMigration();
        }
        $this->createController();

        if (!$this->option('no-views')) {
            $this->createViews();
        }

        $this->type = 'Request';

        $this->createRequest('Store');

        $this->createRequest('Update');
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory()
    {
        $factory = Str::studly(class_basename($this->argument('name')));

        $this->call('cray:factory', [
            'name' => "{$factory}Factory",
            '--model' => $this->argument('name'),
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

        $this->call('cray:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a controller for the model.
     *
     * @return void
     */
    protected function createController()
    {
        $controller = Str::studly(class_basename($this->argument('name')));

        $modelName = $this->qualifyClass($this->getNameInput());

        $args = [
            'name' => "{$controller}Controller",
            '--model' => $modelName,
        ];

        if ($this->hasOption('route-base') ) {
            $args['--route-base'] = $this->option('route-base');
        } elseif ($this->hasOption('views-dir')) {
            $args['--route-base'] = $this->option('views-dir');
        }

        $viewsDir = $this->option('views-dir');
        if ($viewsDir) {
            $args['--views-dir'] = $viewsDir;
        }

        $controllerDir = $this->option('controller-dir');
        if ($controllerDir) {
            $args['--controller-dir'] = $controllerDir;
        }

        $this->call('cray:controller', $args);
    }

    protected function createViews()
    {
        $name = $this->argument('name');
        $args = [
            'name' => $name,
            '--all' => true,
        ];

        $dir = $this->option('views-dir');
        if ($dir) {
            $args['--dir'] = $dir;
        }

        if ($this->hasOption('route-base')) {
            $args['--route-base'] = $this->option('route-base');
        }

        $stub = $this->option('stubs-dir');
        if ($stub) {
            $args['--stubs'] = $stub;
        }
        $this->call('cray:view', $args);
    }

    /**
     * Create a controller for the model.
     *
     * @param  string  $requestType
     *
     * @return void
     */
    protected function createRequest($requestType)
    {
        $model = Str::studly(class_basename($this->argument('name')));
        $name = "{$model}{$requestType}Request";
        $this->call('cray:request', [
            'name' => $name,
            '--model' => $model,
            '--type' => Str::slug($requestType),
        ]);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('cray.stubs_dir').'/'.Str::slug($this->type).'.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        switch ($this->type) {
            case 'Request':
                return $rootNamespace.'\Http\Requests';
        }

        return $rootNamespace;
    }
}
