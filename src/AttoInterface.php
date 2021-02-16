<?php
declare(strict_types=1);

namespace ExtendsSoftware\Atto;

use Closure;
use InvalidArgumentException;
use Throwable;

/**
 * Atto Interface.
 *
 * Atto is a tool based on the builder pattern to configure, route and render a website in no time.
 *
 * @package ExtendsSoftware\Atto
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 0.1.0
 * @see     https://github.com/extendssoftware/atto
 */
interface AttoInterface
{
    /**
     * Version of Atto.
     *
     * @var string
     */
    public const VERSION = '0.1.0';

    /**
     * Get/set start callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoInterface|Closure|null The callback when found, null or AttoInterface for method chaining.
     */
    public function start(Closure $callback = null);

    /**
     * Get/set finish callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoInterface|Closure|null The callback when found, null or AttoInterface for method chaining.
     */
    public function finish(Closure $callback = null);

    /**
     * Get/set error callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoInterface|Closure|null The callback when found, null or AttoInterface for method chaining.
     */
    public function error(Closure $callback = null);

    /**
     * Get/set view file.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoInterface|string|null The view filename when set, null or AttoInterface for method chaining.
     */
    public function view(string $filename = null);

    /**
     * Get/set layout file.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoInterface|string|null The layout filename when set, null or AttoInterface for method chaining.
     */
    public function layout(string $filename = null);

    /**
     * Get/set data from/to the container.
     *
     * @param string|null $path  Dot notation path to get/set data for.
     * @param mixed       $value Value to set.
     *
     * @return AttoInterface|mixed|null Data for name when found, all data, null or AttoInterface for method chaining.
     * @throws InvalidArgumentException When path dot notation is wrong.
     */
    public function data(string $path = null, $value = null);

    /**
     * Get/set route.
     *
     * @param string|null  $name     Name of the route.
     * @param string|null  $url      URL to match.
     * @param string|null  $view     Filename to the view file.
     * @param Closure|null $callback Callback to call when route is matched.
     *
     * @return AttoInterface|array|null The route when found, null or AttoInterface for method chaining.
     */
    public function route(string $name = null, string $url = null, string $view = null, Closure $callback = null);

    /**
     * Redirect to URL.
     *
     * @param string     $url        URL or name of the route to redirect to.
     * @param array|null $parameters Parameters for route when previous parameter is the name of a route.
     * @param int|null   $status     HTTP status code to use. Default is 301.
     *
     * @return void
     * @throws Throwable When assembly of the route fails.
     */
    public function redirect(string $url, array $parameters = null, int $status = null): void;

    /**
     * Assemble URL for route.
     *
     * @param string     $name       Name of the route.
     * @param array|null $parameters Route parameters.
     * @param array|null $query      Query string to add to the assembled URL.
     *
     * @return string Assembled URL for route.
     * @throws Throwable When route with name is not found or when a required parameter for the route is not provided.
     */
    public function assemble(string $name, array $parameters = null, array $query = null): string;

    /**
     * Match route for URL path.
     *
     * @param string $path   URL path to find matching route for.
     * @param string $method Request method.
     *
     * @return array|null Matched route or null when no route can be matched.
     */
    public function match(string $path, string $method): ?array;

    /**
     * Render file with PHP include.
     *
     * @param string      $filename Filename to render or string to return.
     * @param object|null $newThis  New current object for the included file.
     *
     * @return string Rendered content from the file or the string when not a file.
     * @throws Throwable When the file throws a Throwable.
     */
    public function render(string $filename, object $newThis = null): string;

    /**
     * Call a callback.
     *
     * @param Closure    $callback  Callback to call.
     * @param object     $newThis   Current object for the callback.
     * @param array|null $arguments Callback arguments with the key matching the name of the argument.
     *
     * @return mixed Result of the callback.
     * @throws Throwable When callback reflection fails or a required argument is missing.
     */
    public function call(Closure $callback, object $newThis, array $arguments = null);

    /**
     * Run Atto in four steps.
     *
     * @param string|null $path   URL path to match. Default is REQUEST_URI from the server environment.
     * @param string|null $method Request method. Default is REQUEST_METHOD from the server environment.
     *
     * @return string Rendered content. Or the Throwable message on error.
     */
    public function run(string $path = null, string $method = null): string;
}
