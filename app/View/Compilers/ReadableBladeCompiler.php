<?php

namespace App\View\Compilers;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;

class ReadableBladeCompiler extends BladeCompiler
{
    /**
     * Return a human-readable compiled path derived from the view's relative
     * path instead of an opaque hash.
     *
     * e.g. resources/views/teacher/grading/index.blade.php
     *   → storage/framework/views/teacher_grading_index.php
     */
    public function getCompiledPath($path): string
    {
        // Strip the application base path
        $relative = Str::after($path, $this->basePath);
        $relative = ltrim($relative, '/\\');

        // Drop the resources/views/ prefix when present
        $relative = preg_replace('#^resources[/\\\\]views[/\\\\]#i', '', $relative);

        // Remove .blade.php or .php extension
        $name = preg_replace('#\.blade\.php$|\.php$#i', '', $relative);

        // Flatten directory separators to underscores and sanitize
        $name = str_replace(['/', '\\'], '_', $name);
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $name = trim($name, '_');

        // Fall back to a hash if we ended up with an empty string
        if ($name === '') {
            $name = hash('xxh128', 'v2' . Str::after($path, $this->basePath));
        }

        return $this->cachePath . DIRECTORY_SEPARATOR . $name . '.' . $this->compiledExtension;
    }
}
