<?php

namespace JunaidQadirB\Cray\Console\Commands;

use Config;
use Illuminate\Support\Str;
use JunaidQadirB\Cray\Console\Contracts\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ViewMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cray:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new View';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    private $fileName = 'index';

    private string $baseViewPath;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /*  if (parent::handle() === false && ! $this->option('force')) {
              return;
          }*/
        if (!$this->option('index') && !$this->option('create') && !$this->option('edit') && !$this->option('show') && !$this->option('all')) {
            $this->input->setOption('all', true);
        }
        $this->createView();
    }

    /**
     * Create a view for the model.
     *
     * @return void
     */
    protected function createView()
    {
        $path = $this->createViewDirectory();

        if ($this->option('all')) {
            $this->buildView('index', $path);
            $this->buildView('create', $path);
            $this->buildView('edit', $path);
            $this->buildView('show', $path);
            $this->createDeleteView($path);
            return;
        }

        if ($this->option('index') || $this->option('all')) {
            $this->input->setOption('index', true);
            $this->buildView('index', $path);
            $this->createDeleteView($path);
        }

        if ($this->option('create') || $this->option('all')) {
            $this->input->setOption('create', true);
            $this->buildView('create', $path);
        }

        if ($this->option('edit') || $this->option('all')) {
            $this->input->setOption('edit', true);
            $this->buildView('edit', $path);
        }

        if ($this->option('show') || $this->option('all')) {
            $this->input->setOption('show', true);
            $this->buildView('show', $path);
        }
    }

    /**
     *
     */
    protected function createViewDirectory()
    {
        $name = Str::studly(class_basename($this->argument('name')));
        $viewDirSlug = Str::slug(Str::plural(str_to_words($name), 2));
        $this->baseViewPath = $viewPath = $this->chooseViewPath();

        $dir = $this->option('dir');

        $path = $viewPath.'/'.$viewDirSlug;

        if ($dir) {
            $path = $viewPath.'/'.$dir.'/'.$viewDirSlug;
        }

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    private function chooseViewPath()
    {
        if (!Config::has('view.paths') || (Config::has('view.paths') && count(Config::get('view.paths')) < 1)) {
            return $this->viewPath();
        }

        if (count(Config::get('view.paths')) == 1) {
            return Config::get('view.paths')[0];
        }

        return $this->choice('Where would you like to put your views?', Config::get('view.paths'));
    }

    protected function buildView($type, $path)
    {
        $name = Str::studly(class_basename($this->argument('name')));
        $this->fileName = $type;
        $stub = $this->files->get($this->getStub());
        $viewLabel = Str::plural(str_to_words($name), 2);
        $viewName = Str::camel($viewLabel);
        $stub = $this->replacePlaceholders($stub, $name, $path);

        $target = $path.'/'.$type.'.blade.php';

        if ($type == 'delete') {
            $target = $path.'/modals/'.$type.'.blade.php';
        }
        $displayPath = str_replace(resource_path(), '/resources', $target);
        $message = "View created successfully in {$displayPath}";
        if (file_exists($target) && !$this->option('force')) {
            $this->error("File already exists. Cannot overwrite {$displayPath}.");
        } else {
            if ($this->option('force')) {
                $message = "View overwritten successfully in {$displayPath}";
            }
            file_put_contents($target, $stub);
            $this->info($message);
        }
        if ($type == 'create' || $type == 'edit') {
            /**
             * Create the _form partial form the stub
             */
            $formPartial = $path.'/_form.blade.php';
            $formPartialDisplayPath = str_replace(resource_path(), '/resources', $formPartial);
            $formStub = $this->files->get($this->getStub('_form'));

            if (file_exists($formPartial) && !$this->option('force')) {
//            $this->error("File already exists. Cannot overwrite {$formPartialDisplayPath}.");
            } else {
                if (config('cray.fields.generate')) {
                    $tableName = Str::plural(Str::snake(class_basename($this->argument('name'))));
                    $fields = \JunaidQadirB\Cray\Cray::fields($tableName, null, true);
                    file_put_contents($formPartial, $fields);
                } else {
                    file_put_contents($formPartial, $formStub);
                }
                $this->info("View created successfully in {$formPartialDisplayPath}");
            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @param  null|string  $fileName
     * @return string
     */
    protected function getStub($fileName = null)
    {
        if (isset($fileName)) {
            $this->fileName = $fileName;
        }
        $stubsPath = "stubs/view/{$this->fileName}.stub";
        $stubs = $this->option('stubs');

        if ($stubs) {
            $stubsPath = $stubs.'/'.$this->fileName.".stub";
        }
        return resource_path($stubsPath);
    }

    /**
     * Replace all placeholders
     *
     * @param $stub
     * @param $name
     * @param  null  $path
     *
     * @return mixed
     */
    protected function replacePlaceholders($stub, $name, $path = null)
    {
        $path = trim(str_replace($this->baseViewPath, '', $path), "/");
        $path = str_replace('/', '.', $path);
        $modelSlug = Str::slug(Str::plural(str_to_words($name), 2));

        $viewLabel = str_to_words($name);
        $viewLabelPlural = Str::plural(str_to_words($name));
        $viewName = Str::camel($name);

        $replace = array_merge([], [
            '$label$' => $viewLabel,
            '$labelPlural$' => $viewLabelPlural,
            '$name$' => $viewName,
            '$modelSlug$' => $modelSlug,
            '$model$' => $name,
            '$rows$' => '$'.Str::camel(Str::plural($name, 2)),
            '$row$' => '$'.Str::camel(Str::singular($name)),
            '$routeBase$' => $this->option('route-base') ?: $path,
            '$viewDir$' => $path,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
    }

    protected function createDeleteView($path)
    {
        if (!file_exists($path.'/modals')) {
            mkdir($path.'/modals');
        }

        $this->buildView('delete', $path);
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
        return $rootNamespace;
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Generate an index, create, and an edit view for the model',
            ],

            ['index', 'i', InputOption::VALUE_NONE, 'Create a only the index view for the model'],

            ['create', 'c', InputOption::VALUE_NONE, 'Create only the create view for the model'],

            ['edit', 'e', InputOption::VALUE_NONE, 'Create only the edit view for the model'],

            ['show', 's', InputOption::VALUE_NONE, 'Create only the show view for the model'],

            ['force', 'f', InputOption::VALUE_NONE, 'Create the file even if the file already exists.'],

            ['dir', 'd', InputOption::VALUE_OPTIONAL, 'Create the file inside this directory within the view.'],

            ['stubs', 'b', InputOption::VALUE_OPTIONAL, 'Use stubs from the specified directory.'],

            ['route-base', 'r', InputOption::VALUE_OPTIONAL, 'Use the provided route base when generating views.'],
        ];
    }
}
