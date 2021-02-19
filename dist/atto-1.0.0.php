<?php
declare(strict_types=1);

/**
 * AttoPHP Interface.
 *
 * AttoPHP is a tool based on the builder pattern to configure, route and render a website in no time.
 *
 * @package ExtendsSoftware\AttoPHP
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 1.0.0
 * @see     https://github.com/extendssoftware/atto-php
 */
interface AttoPHPInterface
{
    /**
     * Version of AttoPHP.
     *
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * Get/set start callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoPHPInterface|Closure|null The callback when found, null or AttoPHPInterface for method chaining.
     */
    public function start(Closure $callback = null);

    /**
     * Get/set finish callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoPHPInterface|Closure|null The callback when found, null or AttoPHPInterface for method chaining.
     */
    public function finish(Closure $callback = null);

    /**
     * Get/set error callback.
     *
     * @param Closure|null $callback
     *
     * @return AttoPHPInterface|Closure|null The callback when found, null or AttoPHPInterface for method chaining.
     */
    public function error(Closure $callback = null);

    /**
     * Set root path for templates.
     *
     * @param string|null $path Path to the templates directory.
     *
     * @return AttoPHPInterface|string|null The root path when set, null or AttoPHPInterface for method chaining.
     */
    public function root(string $path = null);

    /**
     * Get/set view file.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoPHPInterface|string|null The view filename when set, null or AttoPHPInterface for method chaining.
     */
    public function view(string $filename = null);

    /**
     * Get/set layout file.
     *
     * @param string|null $filename Filename to set.
     *
     * @return AttoPHPInterface|string|null The layout filename when set, null or AttoPHPInterface for method chaining.
     */
    public function layout(string $filename = null);

    /**
     * Get/set data from/to the container.
     *
     * @param string|null $path  Dot notation path to get/set data for.
     * @param mixed       $value Value to set.
     *
     * @return AttoPHPInterface|mixed|null Data for name when found, all data, null or AttoPHPInterface for method
     *                                     chaining.
     * @throws InvalidArgumentException When path dot notation is wrong.
     */
    public function data(string $path = null, $value = null);

    /**
     * Get/set route.
     *
     * @param string|null  $name     Name of the route.
     * @param string|null  $pattern  URL pattern to match.
     * @param string|null  $view     Filename to the view file.
     * @param Closure|null $callback Callback to call when route is matched.
     *
     * @return AttoPHPInterface|array|null The route when found, null or AttoPHPInterface for method chaining.
     */
    public function route(string $name = null, string $pattern = null, string $view = null, Closure $callback = null);

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
     * Run AttoPHP in four steps.
     *
     * @param string|null $path   URL path to match. Default is REQUEST_URI from the server environment.
     * @param string|null $method Request method. Default is REQUEST_METHOD from the server environment.
     *
     * @return string Rendered content. Or the Throwable message on error.
     */
    public function run(string $path = null, string $method = null): string;
}

/**
 * Implementation of AttoPHPInterface.
 *
 * @package ExtendsSoftware\AttoPHP
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 1.0.0
 * @see     https://github.com/extendssoftware/atto-php
 */
class AttoPHP implements AttoPHPInterface
{
    /**
     * Templates root.
     *
     * @var string|null
     */
    protected ?string $root = null;

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
     * Matched route.
     *
     * @var array|null
     */
    protected ?array $matched = null;

    /**
     * Data container.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Start callback.
     *
     * @var Closure|null
     */
    protected ?Closure $start = null;

    /**
     * Finish callback.
     *
     * @var Closure|null
     */
    protected ?Closure $finish = null;

    /**
     * Error callback.
     *
     * @var Closure|null
     */
    protected ?Closure $error = null;

    /**
     * @inheritDoc
     */
    public function start(Closure $callback = null)
    {
        if ($callback === null) {
            return $this->start;
        }

        $this->start = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finish(Closure $callback = null)
    {
        if ($callback === null) {
            return $this->finish;
        }

        $this->finish = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function error(Closure $callback = null)
    {
        if ($callback === null) {
            return $this->error;
        }

        $this->error = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function root(string $path = null)
    {
        if ($path === null) {
            return $this->root;
        }

        $this->root = $path;

        return $this;
    }

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
     */
    public function data(string $path = null, $value = null)
    {
        if ($path === null) {
            return $this->data;
        }

        if (!preg_match('~^(?<path>([a-z0-9]+)((?:\.([a-z0-9]+))*))$~i', $path, $matches)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not a valid dot notation. Please fix the ' .
                'notation. The colon (:), dot (.) and slash (/) characters can be used as separator. The can be used ' .
                'interchangeably. The characters between the separator can only consist of a-z and 0-9, case ' .
                'insensitive.', $path));
        }

        $reference = &$this->data;
        $nodes = preg_split('~[:./]~', $matches['path']);

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
    public function route(string $name = null, string $pattern = null, string $view = null, Closure $callback = null)
    {
        if ($name === null) {
            return $this->matched;
        }

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
    public function assemble(string $name, array $parameters = null, array $query = null): string
    {
        $route = $this->route($name);
        if (!$route) {
            throw new RuntimeException(sprintf(
                'No route found with name "%s". Please check the name of the route or give a new route with the ' .
                'same name.',
                $name
            ));
        }

        $parameters ??= [];
        $url = $route['pattern'];

        // Replace and save constraint suffix from route pattern parameters.
        $constraints = [];
        $url = preg_replace_callback('~:(?<parameter>[a-z]\w*)<(?<constraint>[^>]+)>~i', static function ($match) use (&$constraints) {
            $parameter = $match['parameter'];
            $constraints[$parameter] = $match['constraint'];

            return ':' . $parameter;
        }, $url);

        do {
            // Match optional parts inside out. Match everything inside brackets except a opening or closing bracket.
            $url = preg_replace_callback('~\[(?<optional>[^\[\]]+)]~', static function ($match) use ($name, $parameters, $constraints): string {
                $optional = $match['optional'];

                // Find all parameters in optional part.
                if (preg_match_all('~:(?<parameter>[a-z]\w*)~i', $optional, $matches)) {
                    foreach ($matches['parameter'] as $parameter) {
                        if (!isset($parameters[$parameter])) {
                            // Parameter is not specified, skip whole optional part.
                            return '';
                        }

                        $value = (string)$parameters[$parameter];
                        if (isset($constraints[$parameter])) {
                            $constraint = $constraints[$parameter];

                            // Check constraint for parameter value.
                            if (!preg_match('~^' . $constraint . '$~i', $value)) {
                                throw new RuntimeException(sprintf(
                                    'Value "%s" for parameter "%s" is not allowed by constraint "%s" for route with ' .
                                    'name "%s". Please give a valid value.',
                                    $value,
                                    $parameter,
                                    $constraint,
                                    $name
                                ));
                            }
                        }

                        // Replace parameter definition with value.
                        $optional = str_replace(':' . $parameter, $value, $optional);
                    }
                }

                return $optional;
            }, $url, -1, $count);
        } while ($count > 0);

        // Find all required parameters.
        $url = preg_replace_callback('~:(?<parameter>[a-z]\w*)~i', static function ($match) use ($name, $parameters, $constraints): string {
            $parameter = $match['parameter'];
            if (!isset($parameters[$parameter])) {
                throw new RuntimeException(sprintf(
                    'Required parameter "%s" for route name "%s" is missing. Please give the required parameter ' .
                    'or change the route URL.',
                    $parameter,
                    $name
                ));
            }

            $value = (string)$parameters[$parameter];
            if (isset($constraints[$parameter])) {
                $constraint = $constraints[$parameter];

                // Check constraint for parameter value.
                if (!preg_match('~^' . $constraint . '$~i', $value)) {
                    throw new RuntimeException(sprintf(
                        'Value "%s" for parameter "%s" is not allowed by constraint "%s" for route with name "%s". ' .
                        'Please give a valid value.',
                        $value,
                        $parameter,
                        $constraint,
                        $name
                    ));
                }
            }

            return $value;
        }, $url);

        // Remove asterisk from URL.
        $url = str_replace('*', '', $url);

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * @inheritDoc
     */
    public function match(string $path, string $method): ?array
    {
        $path = strtok($path, '?');
        foreach ($this->routes as $route) {
            // Replace and save HTTP methods prefix from route pattern.
            $methods = [];
            $pattern = preg_replace_callback('~^(?<methods>\s*([a-z]+(\s*\|\s*[a-z]+)*)\s*)~i', static function ($match) use (&$methods): string {
                $methods = array_map('trim', explode('|', strtoupper($match['methods'])));

                return '';
            }, $route['pattern']);

            if ($methods && !in_array($method, $methods, true)) { // Only check for HTTP methods when provided.
                continue;
            }

            // Replace and save constraint suffix from route pattern parameters.
            $constraints = [];
            $pattern = preg_replace_callback('~:(?<parameter>[a-z]\w*)<(?<constraint>[^>]+)>~i', static function ($match) use (&$constraints) {
                $parameter = $match['parameter'];
                $constraints[$parameter] = $match['constraint'];

                return ':' . $parameter;
            }, $pattern);

            // Replace asterisk to match an character.
            $pattern = str_replace('*', '(.*)', $pattern);

            do {
                // Replace everything inside brackets with an optional regular expression group inside out.
                $pattern = preg_replace('~\[([^\[\]]+)]~', '($1)?', $pattern, -1, $count);
            } while ($count > 0);

            // Replace all parameters with a named regular expression group which will not match a forward slash or the parameter constraint.
            $pattern = preg_replace_callback('~:(?<parameter>[a-z]\w*)~i', static function ($match) use ($constraints): string {
                return sprintf(
                    '(?<%s>%s)',
                    $match['parameter'],
                    $constraints[$match['parameter']] ?? '[^/]+'
                );
            }, $pattern);

            if (preg_match('~^' . $pattern . '$~', $path, $matches)) {
                $route['matches'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $route;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @noinspection PhpIncludeInspection
     */
    public function render(string $filename, object $newThis = null): string
    {
        $closure = function () use ($filename) {
            ob_start();
            try {
                if (is_file($filename)) {
                    include $filename;
                } else {
                    $root = $this->root();
                    if ($root && is_file($root . $filename)) {
                        include $root . $filename;
                    } else {
                        echo $filename;
                    }
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
                        'default value and is not nullable. Please give the missing argument or give it a default ' .
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
    public function run(string $path = null, string $method = null): string
    {
        try {
            $callback = $this->start();
            if ($callback) {
                $return = $this->call($callback, $this);
                if ($return) {
                    return (string)$return;
                }
            }

            $route = $this->match($path ?: $_SERVER['REQUEST_URI'], $method ?: $_SERVER['REQUEST_METHOD']);
            if ($route) {
                $this->matched = $route;

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

                $this->data('atto.view', $render);
            }

            $layout = $this->layout();
            if ($layout) {
                $render = $this->render($layout, $this);
            }

            $callback = $this->finish();
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
                $callback = $this->error();
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
