<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

class ServeCommand extends BaseServeCommand
{
    /**
     * Get the full server command.
     *
     * @return array<int, string>
     */
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        array_splice($command, 1, 0, [
            '-d',
            'upload_max_filesize=2048M',
            '-d',
            'post_max_size=2048M',
            '-d',
            'memory_limit=-1',
        ]);

        return $command;
    }
}
