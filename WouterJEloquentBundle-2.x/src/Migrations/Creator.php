<?php

/*
 * This file is part of the WouterJEloquentBundle package.
 *
 * (c) 2014 Wouter de Jong
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WouterJ\EloquentBundle\Migrations;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Migrations\MigrationCreator;
use Symfony\Bundle\MakerBundle\FileManager;

/**
 * A bridge between Illuminate\Database and Symfony.
 *
 * This removes the dependency on Illuminate\Filesystem in favor
 * of MakerBundle's FileManager.
 *
 * @final
 * @internal
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class Creator extends MigrationCreator
{
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function create($name, $path, $table = null, $create = false): string
    {
        $path = $this->getPath($name, $path);
        $stub = $this->getStub($table, $create);

        if (!trait_exists(WithoutModelEvents::class)) {
            /** @psalm-suppress TooManyArguments BC with Laravel <9 */
            $populatedStub = $this->populateStub($name, $stub, $table);
        } else {
            /** @psalm-suppress TooFewArguments BC with Laravel <9 */
            $populatedStub = $this->populateStub($stub, $table);
        }
        $this->fileManager->dumpFile($path, $populatedStub);

        /** @psalm-suppress TooManyArguments BC with Laravel <9 */
        $this->firePostCreateHooks($table, $path);

        return $path;
    }

    protected function getStub($table, $create): string
    {
        $eloquent7 = file_exists($this->stubPath().'/migration.stub');
        if ($eloquent7) {
            $file = 'migration.stub';
            if (null !== $table) {
                $file = $create ? 'migration.create.stub' : 'migration.update.stub';
            }
        } else {
            $file = 'blank.stub';
            if (null !== $table) {
                $file = $create ? 'create.stub' : 'update.stub';
            }
        }

        // TODO add support to overwrite stub templates (ref https://github.com/illuminate/database/commit/b0300976c7a496bcaef5917b9efe72039b3d947a)

        $stubContents = file_get_contents($this->stubPath().'/'.$file);
        $stubContents = str_replace('Illuminate\Support\Facades', 'WouterJ\EloquentBundle\Facade', $stubContents);

        return $stubContents;
    }
}
