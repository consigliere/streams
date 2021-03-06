<?php

namespace RAD\Streams;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use RAD\Streams\Models\Permission;
use RAD\Streams\Models\Setting;
use RAD\Streams\Models\User;

class Streams
{
    /**
     *  Singleton Streams Class.
     */
    private static $instance;

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function getVersion()
    {
        $composer_lock = __DIR__.'/../../../../composer.lock';
        $version = null;

        if (File::exists($composer_lock)) {
            // Get the composer.lock file
            $file = json_decode(
                File::get($composer_lock)
            );

            // Loop through all the packages and get the version of streams
            foreach ($file->packages as $package) {
                if ($package->name == 'rad/streams') {
                    $version = $package->version;
                    break;
                }
            }
        }

        return $version;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    /**
     *  End Singleton operators.
     *
     * @param $key
     * @param null $default
     *
     * @return null
     */
    public static function setting($key, $default = null)
    {
        $setting = Setting::where('key', '=', $key)->first();
        if (isset($setting->id)) {
            return $setting->value;
        }

        return $default;
    }

    public static function image($file, $default = '')
    {
        if (!empty($file) && Storage::exists(config('streams.storage.subfolder').$file)) {
            return Storage::url(config('streams.storage.subfolder').$file);
        }

        return $default;
    }

    public static function routes()
    {
        require __DIR__.'/../routes/streams.php';
    }

    public static function can($permission)
    {
        // Check if permission exist
        $exist = Permission::where('key', $permission)->first();

        if ($exist) {
            $user = User::find(Auth::id());
            if (!$user->hasPermission($permission)) {
                throw new UnauthorizedHttpException(null);
            }
        }
    }
}
