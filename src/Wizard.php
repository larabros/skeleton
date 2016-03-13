<?php

namespace Larabros\Skeleton;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Script\Event;

class Wizard
{
    private static $keys = [;
        ':author_name',
        ':author_username',
        ':author_website',
        ':author_email',
        ':vendor_name',
        ':vendor_ns',
        ':package_name',
        ':package_ns',
        ':package_description',
        ':styleci_repo',
    ];

    private static $answers = [];

    public static function init(Event $event)
    {
        $io       = $event->getIO();
        $basePath = realpath(__DIR__.'/../');

        // Iterate through `$keys` and get the answers
        foreach (static::$keys as $key) {
            $question = 'What is the '.str_replace('_', ' ', $key) .'?';
            static::$answers[$key] = self::ask($io, $question, 'Larabros');
        }

        self::recursiveJob($basePath, self::rename());

        $json = new JsonFile(Factory::getComposerFile());
        $composerDefinition = self::getDefinition(
            '',
            '',
            $answers[':vendor_ns'].'/'.$answers[':package_ns'],
            ''
        );
        self::$packageName = [$vendorClass, $packageClass];
        // Update composer definition
        $json->write($composerDefinition);
        $io->write("<info>composer.json for {$composerDefinition['name']} is created.\n</info>");
    }

    /**
     * @param IOInterface $io
     * @param string      $question
     * @param string      $default
     *
     * @return string
     */
    private static function ask(IOInterface $io, $question, $default)
    {
        $ask = [
            sprintf("\n<question>%s</question>\n", $question),
            sprintf("\n(<comment>%s</comment>):", $default)
        ];
        $answer = $io->ask($ask, $default);

        return $answer;
    }

    /**
     * @param string   $path
     * @param Callable $job
     *
     * @return void
     */
    private static function recursiveJob($path, $job)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $job($file);
        }
    }

    /**
     * @param string   $name
     * @param string   $vendor
     * @param string   $package
     * @param string   $packageName
     * @param JsonFile $json
     *
     * @return array
     */
    private static function getDefinition($vendor, $package, $packageName, JsonFile $json)
    {
        $composerDefinition = $json->read();
        unset($composerDefinition['scripts']['post-create-project-cmd']);
        $composerDefinition['name'] = $packageName;

        return $composerDefinition;
    }

    /**
     *
     * @return \Closure
     */
    private static function rename()
    {
        $jobRename = function (\SplFileInfo $file) {
            $filename = $file->getFilename();
            if ($file->isDir() || strpos($filename, '.') === 0 || !is_writable($file)) {
                return;
            }

            $contents = file_get_contents($file);
            // Fill an array with / characters to the same length as the number of search
            // strings. This is required for preg_quote() to work properly
            $delims = array_fill(0, count(static::$keys), '/');

            // Apply preg_quote() to each search string so it is safe to use in the regex
            $quotedSearches = array_map('preg_quote', static::$keys, $delims);

            $expr = '/\b('.implode('|', $quotedSearches).')\b/';

            $callback = function($match) use($replacements) {
              return $replacements[$match[1]];
            };

            $contents = preg_replace_callback($expr, $callback, $string);
            file_put_contents($file, $contents);
        };

        return $jobRename;
    }
}
