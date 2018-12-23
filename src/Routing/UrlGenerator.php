<?php

namespace Drmer\Laravel\Migration\Routing;

use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Routing\UrlRoutable;

class UrlGenerator extends BaseUrlGenerator
{
    use InteractsWithTime;

    /**
     * The asset root URL.
     *
     * @var string
     */
    protected $assetRoot;


    /**
     * The encryption key resolver callable.
     *
     * @var callable
     */
    protected $keyResolver;

    /**
     * Create a new URL Generator instance.
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $assetRoot
     * @return void
     */
    public function __construct(RouteCollection $routes, Request $request, $assetRoot = null)
    {
        $this->assetRoot = $assetRoot;

        parent::__construct($routes, $request);
    }

    /**
     * Generate the URL to an application asset.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->assetRoot
                    ? $this->assetRoot
                    : $this->formatRoot($this->formatScheme($secure));

        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Create a signed route URL for a named route.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @param  \DateTimeInterface|int  $expiration
     * @param  bool  $absolute
     * @return string
     */
    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        $parameters = $this->formatParameters($parameters);

        if ($expiration) {
            $parameters = $parameters + ['expires' => $this->availableAt($expiration)];
        }

        ksort($parameters);

        $key = call_user_func($this->keyResolver);

        return $this->route($name, $parameters + [
            'signature' => hash_hmac('sha256', $this->route($name, $parameters, $absolute), $key),
        ], $absolute);
    }

    /**
     * Create a temporary signed route URL for a named route.
     *
     * @param  string  $name
     * @param  \DateTimeInterface|int  $expiration
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     */
    public function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
    {
        return $this->signedRoute($name, $parameters, $expiration, $absolute);
    }

    /**
     * Determine if the given request has a valid signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasValidSignature(Request $request, $absolute = true)
    {
        $url = $absolute ? $request->url() : '/'.$request->path();

        $original = rtrim($url.'?'.Arr::query(
            Arr::except($request->query(), 'signature')
        ), '?');

        $expires = Arr::get($request->query(), 'expires');

        $signature = hash_hmac('sha256', $original, call_user_func($this->keyResolver));

        return  hash_equals($signature, (string) $request->query('signature', '')) &&
               ! ($expires && Carbon::now()->getTimestamp() > $expires);
    }


    /**
     * Format the given controller action.
     *
     * @param  string|array  $action
     * @return string
     */
    protected function formatAction($action)
    {
        if (is_array($action)) {
            $action = '\\'.implode('@', $action);
        }

        return parent::formatAction($action);
    }

    /**
     * Set the encryption key resolver.
     *
     * @param  callable  $keyResolver
     * @return $this
     */
    public function setKeyResolver(callable $keyResolver)
    {
        $this->keyResolver = $keyResolver;

        return $this;
    }
}
