<?php
declare(strict_types=1);

/**
 * Atto Interface.
 *
 * A tool based on the builder pattern to configure, route and render a small website.
 *
 * @package ExtendsSoftware\Atto
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 0.1.0
 * @see     https://github.com/extendssoftware/extends-atto
 */
interface AttoInterface
{
    /**
     * Event to call callback before routing starts.
     *
     * @var string
     */
    public const CALLBACK_ON_START = 'callbackOnStart';

    /**
     * Event to call callback after render is finished.
     *
     * @var string
     */
    public const CALLBACK_ON_FINISH = 'callbackOnFinish';

    /**
     * Event to call callback when error occurs.
     *
     * @var string
     */
    public const CALLBACK_ON_ERROR = 'callbackOnError';

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
     * Get/set callback for event.
     *
     * The callback will be ignored when the event type is not one of the class constants. When callback is null, the
     * current callback for the event will be returned. Null will be returned when no callback is set.
     *
     * @param string       $event    Type of event when to call the callback.
     * @param Closure|null $callback Callback to call.
     *
     * @return AttoInterface|Closure|null The callback when found, null or AttoInterface for method chaining.
     */
    public function callback(string $event, Closure $callback = null);

    /**
     * Get/set route.
     *
     * When path is null, the route with name will be returned. Null will be returned when no route with name is set.
     *
     * The route pattern can have required and optional parameters. A parameter name must start with a semicolon and a
     * a-z character and can be followed by any amount of a-z characters, 0-9 digits and a underscore. All case
     * insensitive.
     *
     * @param string       $name     Name of the route.
     * @param string|null  $pattern  URL path pattern to match.
     * @param string|null  $view     Filename to the view file.
     * @param Closure|null $callback Callback to call when route is matched.
     *
     * @return AttoInterface|array|null The route when found, null or AttoInterface for method chaining.
     * @see AttoInterface::assemble() For more information about matching optional and required parameters.
     */
    public function route(string $name, string $pattern = null, string $view = null, Closure $callback = null);

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
     * @param string     $name       Name of the route.
     * @param array|null $parameters Route parameters.
     *
     * @return string Assembled URL for route.
     * @throws Throwable When route with name is not found or when a required parameter for the route is not provided.
     */
    public function assemble(string $name, array $parameters = null): string;

    /**
     * Match route for URL path.
     *
     * Query string will be ignored when matching a route. A matched route will be enhanced with matched URL parameters.
     *
     * Every parameter (e.g. :foo) will match any character except a forward slash (/). The whole route pattern must
     * match the URL path before the route is considered a match. If the webserver add/removes a trailing slash from the
     * URL, the same has to be done with the route pattern. The URL path /foo/ will not match the route /foo, and /foo
     * will not match /foo/.
     *
     * The route pattern "*" is considered a catch-all route and will match any URL path. It's a good practise to always
     * add a catch-all route as last route. This route can, for example, be used to redirect to a 404 page. Routes
     * will be matched in order they are added (FIFO). Register the mostly used routes first for marginal gains.
     *
     * @param string|null $path URL path to find matching route for.
     *
     * @return array|null Matched route or null when no route can be matched.
     */
    public function match(string $path): ?array;

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
     * and without the use of eval(). The CALLBACK_ON_FINISH callback can be used to use a parsing engine for this.
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
     * First, when a CALLBACK_ON_START callback is set, this callback will be called. When the callback returns a
     * truly value, this value will be directly return and the execution ends. The return value will be cast to a
     * string. Ideal callback for something like returning cached pages.
     *
     * Secondly, Atto tries to find a matching route for (the provided) URL path. When a route matches and has a
     * filename to a view file set, this filename will be set as view. When a route matches and has a callback, this
     * callback is called. When the callback returns a truly value, this value will be directly returned and the
     * execution ends. The return value will be cast to a string.
     *
     * Thirdly, when a view is set, the view file will be rendered. The rendered view is saved to the data container
     * with the key "view", which can be used in the layout to place the view (e.g. $this->data('view')). When layout is
     * set, the layout will be rendered.
     *
     * Fourthly, when a CALLBACK_ON_FINISH callback is set, this callback will be called. The rendered content will be
     * available to the callback as the argument "render" (e.g. function(string $render) {}). When the callback returns
     * a truly value, this value will replace the rendered content. The return value will be cast to a string. Ideal if
     * you save the rendered content or want to add some content (e.g. render time for the page).
     *
     * When an Throwable occurs and a CALLBACK_ON_ERROR callback is set, this callback will be called with the Throwable
     * as argument with the key "throwable" (e.g. function(Throwable $throwable) {}). If this callback doesn't do a
     * redirect, the Throwable message will be returned, even when the callback throws a Throwable. This callback is
     * ideal for logging or caching a whole rendered page.
     *
     * @param string|null $path URL path to match. Default is REQUEST_URI from the server environment.
     *
     * @return string Rendered content. Or the Throwable message on error.
     */
    public function run(string $path = null): string;
}

/**
 * Implementation of AttoInterface.
 *
 * @package ExtendsSoftware\Atto
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 0.1.0
 * @see     https://github.com/extendssoftware/extends-atto
 */
class Atto implements \ExtendsSoftware\Atto\AttoInterface
{
    /**
     * Filename for view file.
     *
     * @var string|null
     */
    protected ?string $view = null;

    /**
     * Filename for layout file.
     *
     * @var string|null
     */
    protected ?string $layout = null;

    /**
     * Routes in chronological order.
     *
     * @var array[]
     */
    protected array $routes = [];

    /**
     * Data container.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Callbacks for types of events.
     *
     * @var Closure[]
     */
    protected array $callbacks = [];

    /**
     * @inheritDoc
     */
    public function view(string $filename = null)
    {
        if ($filename === null) {
            return $this->view;
        }

        $this->view = $filename;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function layout(string $filename = null)
    {
        if ($filename === null) {
            return $this->layout;
        }

        $this->layout = $filename;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * Validate: '/^([a-z0-9]+)((?:\.([a-z0-9]+))*)$/i'
     */
    public function data(string $path = null, $value = null)
    {
        if ($path === null) {
            return $this->data;
        }

        if (!preg_match('~^([a-z0-9]+)((?:\.([a-z0-9]+))*)$~i', $path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not a valid dot notation. Please fix the ' .
                'notation. The colon (:), dot (.) and slash (/) characters can be used as separator. The can be used ' .
                'interchangeably. The characters between the separator can only consist of a-z and 0-9, case ' .
                'insensitive.', $path));
        }

        $reference = &$this->data;
        $nodes = preg_split('~[:./]~', $path);

        if ($value === null) {
            foreach ($nodes as $node) {
                if (is_array($reference) && array_key_exists($node, $reference)) {
                    $reference = &$reference[$node];
                } else {
                    return null;
                }
            }

            return $reference;
        }

        foreach ($nodes as $node) {
            if (!array_key_exists($node, $reference) || !is_array($reference[$node])) {
                $reference[$node] = [];
            }

            $reference = &$reference[$node];
        }

        $reference = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function callback(string $event, Closure $callback = null)
    {
        if ($callback === null) {
            return $this->callbacks[$event] ?? null;
        }

        $this->callbacks[$event] = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function route(string $name, string $pattern = null, string $view = null, Closure $callback = null)
    {
        if ($pattern === null) {
            return $this->routes[$name] ?? null;
        }

        $this->routes[$name] = [
            'name' => $name,
            'pattern' => $pattern,
            'view' => $view,
            'callback' => $callback,
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redirect(string $url, array $parameters = null, int $status = null): void
    {
        $route = $this->route($url);
        if ($route) {
            $url = $this->assemble($url, $parameters);
        }

        header('Location: ' . $url, true, $status ?: 301);
    }

    /**
     * @inheritDoc
     */
    public function assemble(string $name, array $parameters = null): string
    {
        $route = $this->route($name);
        if (!$route) {
            throw new RuntimeException(sprintf(
                'No route found with name "%s". Please check the name of the route or provide a new route with the ' .
                'same name.',
                $name
            ));
        }

        $parameters ??= [];
        $url = $route['pattern'];
        do {
            // Match optional parts inside out. Match everything inside brackets except a opening or closing bracket.
            $url = preg_replace_callback('~\[([^\[\]]+)]~', static function ($match) use ($parameters): string {
                try {
                    // Find parameters and check if parameter is provided.
                    return preg_replace_callback('~:([a-z][a-z0-9_]+)~i', static function ($match) use ($parameters): string {
                        $parameter = $match[1];
                        if (!isset($parameters[$parameter])) {
                            throw new RuntimeException('');
                        }

                        return (string)$parameters[$parameter];
                    }, $match[1]);
                } catch (Throwable $throwable) {
                    // Parameter for optional part not provided. Skip whole optional part and continue assembly.
                    return $throwable->getMessage();
                }
            }, $url, -1, $count);
        } while ($count > 0);

        // Find all required parameters.
        return preg_replace_callback('~:([a-z][a-z0-9_]+)~i', static function ($match) use ($route, $parameters): string {
            $parameter = $match[1];
            if (!isset($parameters[$parameter])) {
                throw new RuntimeException(sprintf(
                    'Required parameter "%s" for route name "%s" is missing. Please provide the required parameter ' .
                    'or change the route URL.',
                    $parameter,
                    $route['name']
                ));
            }

            return (string)$parameters[$parameter];
        }, $url);
    }

    /**
     * @inheritDoc
     */
    public function match(string $path): ?array
    {
        $path = strtok($path, '?');
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            if ($pattern === '*') {
                return $route;
            }

            do {
                // Replace everything inside brackets with an optional regular expression group inside out.
                $pattern = preg_replace('~\[([^\[\]]+)]~', '($1)?', $pattern, -1, $count);
            } while ($count > 0);

            // Replace all parameters with a named regular expression group which will not match a forward slash.
            $pattern = preg_replace('~:([a-z][a-z0-9_]*)~i', '(?<$1>[^/]+)', $pattern);
            $pattern = '~^' . $pattern . '$~';
            if (preg_match($pattern, $path, $matches)) {
                $route['matches'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $route;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function render(string $filename, object $newThis = null): string
    {
        $closure = function () use ($filename) {
            ob_start();
            try {
                if (is_file($filename)) {
                    /** @noinspection PhpIncludeInspection */
                    include $filename;
                } else {
                    echo $filename;
                }

                return ob_get_clean();
            } catch (Throwable $throwable) {
                // Clean any output for only the error message to show.
                ob_end_clean();

                throw $throwable;
            }
        };

        return $closure->call($newThis ?: $this);
    }

    /**
     * @inheritDoc
     */
    public function call(Closure $callback, object $newThis, array $arguments = null)
    {
        $reflection = new ReflectionFunction($callback);
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!array_key_exists($name, $arguments ?? [])) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $args[] = null;
                } else {
                    throw new RuntimeException(sprintf(
                        'Required argument "%s" for callback is not provided in the arguments array, does not has a ' .
                        'default value and is not nullable. Please provide the missing argument or give it a default ' .
                        'value.',
                        $name
                    ));
                }
            } else {
                $args[] = $arguments[$name];
            }
        }

        return $callback->call($newThis, ...$args ?? []);
    }

    /**
     * @inheritDoc
     */
    public function run(string $path = null): string
    {
        try {
            $callback = $this->callback(static::CALLBACK_ON_START);
            if ($callback) {
                $return = $this->call($callback, $this);
                if ($return) {
                    return (string)$return;
                }
            }

            $route = $this->match($path ?: $_SERVER['REQUEST_URI']);
            if ($route) {
                if ($route['view']) {
                    $this->view($route['view']);
                }

                if ($route['callback']) {
                    $return = $this->call($route['callback'], $this, $route['matches'] ?? []);
                    if ($return) {
                        return (string)$return;
                    }
                }
            }

            $render = '';
            $view = $this->view();
            if ($view) {
                $render = $this->render($view, $this);

                $this->data('view', $render);
            }

            $layout = $this->layout();
            if ($layout) {
                $render = $this->render($layout, $this);
            }

            $callback = $this->callback(static::CALLBACK_ON_FINISH);
            if ($callback) {
                $return = $this->call($callback, $this, [
                    'render' => $render,
                ]);
                if ($return) {
                    return (string)$return;
                }
            }

            return $render;
        } catch (Throwable $throwable) {
            try {
                $callback = $this->callback(static::CALLBACK_ON_ERROR);
                if ($callback) {
                    $return = $this->call($callback, $this, [
                        'throwable' => $throwable,
                    ]);
                    if ($return) {
                        return (string)$return;
                    }
                }
            } catch (Throwable $throwable) {
                return $throwable->getMessage();
            }

            return $throwable->getMessage();
        }
    }
}
