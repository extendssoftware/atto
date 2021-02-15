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
     * When filename is null, current view file will be returned. Null will be returned when filename is not set.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoInterface|string|null The view filename when set, null or AttoInterface for method chaining.
     */
    public function view(string $filename = null);

    /**
     * Get/set layout file.
     *
     * When filename is null, current layout file will be returned. Null will be returned when filename is not set.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoInterface|string|null The layout filename when set, null or AttoInterface for method chaining.
     */
    public function layout(string $filename = null);

    /**
     * Get/set data from/to the container.
     *
     * When value is null, the current data value for the name will be returned. Null will be returned when data for
     * name not exists. When both name and value are null, the whole data container will be returned.
     *
     * The path can contain dot notation, comes in handy when separating data with the same key (e.g. layout.title and
     * blog.title). The colon (:), dot (.) and slash (/) characters can be used as separator. The can be used
     * interchangeably. The characters between the separator can only consist of a-z and 0-9, case insensitive.
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
     * When path is null, the route with name will be returned. Null will be returned when no route with name is set.
     *
     * The route pattern can have required and optional parameters. A parameter name must start with a semicolon and a
     * a-z character and can be followed by any amount of a-z characters, 0-9 digits and a underscore. All case
     * insensitive.
     *
     * When this method is called without route name, the matched route will be returned.
     *
     * @param string|null  $name     Name of the route.
     * @param string|null  $pattern  URL path pattern to match.
     * @param string|null  $view     Filename to the view file.
     * @param Closure|null $callback Callback to call when route is matched.
     *
     * @return AttoInterface|array|null The route when found, null or AttoInterface for method chaining.
     * @see AttoInterface::assemble() For more information about matching optional and required parameters.
     */
    public function route(string $name = null, string $pattern = null, string $view = null, Closure $callback = null);

    /**
     * Redirect to URL.
     *
     * The URL of a route will be used when the name of the route is passed as the URL parameter. When the redirect
     * is done, the execution of the scripts needs to be aborted (.e.g. exit), otherwise execution continues.
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
     * When a part of the route is optional, everything between brackets (e.g. /foo[/:bar-:baz]), and one or more
     * parameters for that part are not provided the whole part will be ignored. So, when the parameter for :id is
     * provided, but the parameter for :slug is not, the whole part between the brackets will be ignored.
     *
     * Optional parts are processed from the inside out. When the inner parameter is not provided
     * (e.g. /foo[:/bar[/:baz]]) but the outer is, the inner part (/:baz) will be ignored, but the outer part
     * (/:bar) will be processed.
     *
     * When a required parameter, every parameter outside brackets (e.g. /foo/:bar), is not provided in the parameters
     * array, a Throwable will be thrown.
     *
     * Optional query string will be added after the route is assembled. An asterisk will be removed from the URL.
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
     * Query string will be ignored when matching a route. A matched route will be enhanced with matched URL
     * parameters.
     *
     * Every parameter (e.g. :foo) will match any character except a forward slash (/) by default. It is possible to
     * add
     * a constraint for a parameter to only match a certain regular expression. A parameter constraint can be added
     * after the parameter and within the < and > character need to be a valid regular expression without the
     * delimiters and is case insensitive (e.g. /blog[:page<\d+>]).. When no constraint is provided, [^/]+ will be used
     * for a parameter, it will match everything till the next forward slash or the end for the URL if no forward
     * string is found.
     *
     * The whole route pattern must match the URL path before the route is considered a match. If the webserver
     * add/removes a trailing slash from the URL, the same has to be done with the route pattern. The URL path /foo/
     * will not match the route /foo, and /foo will not match /foo/.
     *
     * HTTP methods can be prefixed to the route pattern and must be separated with a pipe
     * (e.g. POST|DELETE /blog/:blogId). Default HTTP method is GET when none is given.
     *
     * The asterisk character (*) will match any character in the URL, even a forward slash. It's a good practise to
     * always add a catch-all route as last route using a asterisk. This route can, for example, be used to redirect to
     * a 404 page.
     *
     * Routes will be matched in order they are added (FIFO). Register the mostly used routes first for marginal gains.
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
     * Filename will be included width PHP to support PHP execution inside the file. The current object ($this) will be
     * this AttoInterface, any method from this class or any data set in this class can be used in the included file by
     * using $this (e.g. echo $this->data('title')). File must be a regular and readable file.
     *
     * If the included file throws an Throwable, the output buffer is cleaned and the Throwable message will be
     * returned. This way you only get to see the Throwable message and not a almost fully rendered page with a message
     * somewhere hidden in the source code.
     *
     * When filename is not a file (check with is_file()) then filename will be string and returned by the renderer. By
     * this it is for example possible to get a template from a database. PHP include render does not work with strings
     * and without the use of eval(). The finish callback can be used to use a parsing engine for this.
     *
     * @param string      $filename Filename to render or string to return.
     * @param object|null $newThis  New current object for the included file.
     *
     * @return string Rendered content from the file or the string when not a file.
     * @throws Throwable When the file throws a Throwable.
     * @see AttoInterface::data() Call this method without any arguments to get all the data.
     */
    public function render(string $filename, object $newThis = null): string;

    /**
     * Call a callback.
     *
     * The callback argument(s) will be collected before the callback is called. When a argument from the callback is
     * missing in the arguments array, the default value will be used or null when the argument is nullable.
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
     * First, when a start callback is set, this callback will be called. When the callback returns a truly value, this
     * value will be directly return and the execution ends. The return value will be cast to a string. Ideal callback
     * for something like returning cached pages.
     *
     * Secondly, Atto tries to find a matching route for (the provided) URL path. When a route matches, the route will
     * be saved for further usage. If the route has a filename to a view file set, this filename will be set as view.
     * When a route matches and has a callback, this callback is called. When the callback returns a truly value, this
     * value will be directly returned and the execution ends. The return value will be cast to a string.
     *
     * Thirdly, when a view is set, the view file will be rendered. The rendered view is saved to the data container
     * with the key "view", which can be used in the layout to place the view (e.g. $this->data('view')). When layout
     * is
     * set, the layout will be rendered.
     *
     * Fourthly, when a finish callback is set, this callback will be called. The rendered content will be available to
     * the callback as the argument "render" (e.g. function(string $render) {}). When the callback returns a truly
     * value, this value will replace the rendered content. The return value will be cast to a string. Ideal if you
     * save the rendered content or want to add some content (e.g. render time for the page).
     *
     * When an Throwable occurs and a error callback is set, this callback will be called with the Throwable as
     * argument with the key "throwable" (e.g. function(Throwable $throwable) {}). If this callback doesn't do a
     * redirect, the Throwable message will be returned, even when the callback throws a Throwable. This callback is
     * ideal for logging or caching a whole rendered page.
     *
     * @param string|null $path   URL path to match. Default is REQUEST_URI from the server environment.
     * @param string|null $method Request method. Default is REQUEST_METHOD from the server environment.
     *
     * @return string Rendered content. Or the Throwable message on error.
     */
    public function run(string $path = null, string $method = null): string;
}
