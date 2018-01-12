<?php

namespace SimpleLint;

class LintPhpcsProxy
{
    /**
     * @param $php_bin
     * @param $php_cs
     * @param $command_args
     *
     * @return string
     */
    public static function run(string $php_bin, string $php_cs, array $command_args): string
    {
        $escaped_command_args = array_map(
            function ($arg) {
                return escapeshellarg($arg);
            },
            $command_args
        );
        $escaped_command_args = implode(' ', $escaped_command_args);
        exec(escapeshellcmd($php_bin) . ' ' . escapeshellcmd($php_cs) . ' ' . $escaped_command_args, $output);

        return implode(PHP_EOL, $output);
    }
}
