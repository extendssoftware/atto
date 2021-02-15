<?php
declare(strict_types=1);

namespace ExtendsSoftware\Atto;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function header;
use function http_build_query;
use function is_file;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function strtok;

/**
 * Implementation of AttoInterface.
 *
 * @package ExtendsSoftware\Atto
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 0.1.0
 * @see     https://github.com/extendssoftware/atto
 */
class Atto implements AttoInterface
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

        do {
            // Match optional parts inside out. Match everything inside brackets except a opening or closing bracket.
            $url = preg_replace_callback('~\[(?<optional>[^\[\]]+)]~', static function ($match) use ($parameters): string {
                try {
                    // Find parameters and check if parameter is provided.
                    return preg_replace_callback('~:(?<parameter>[a-z]\w*)~i', static function ($match) use ($parameters): string {
                        $parameter = $match['parameter'];
                        if (!isset($parameters[$parameter])) {
                            throw new RuntimeException('');
                        }

                        return (string)$parameters[$parameter];
                    }, $match['optional']);
                } catch (Throwable $throwable) {
                    // Parameter for optional part not provided. Skip whole optional part and continue assembly.
                    return $throwable->getMessage();
                }
            }, $url, -1, $count);
        } while ($count > 0);

        // Find all required parameters.
        $url = preg_replace_callback('~:(?<parameter>[a-z]\w*)(<(?<constraint>[^>]+)>)?~i', static function ($match) use ($name, $parameters): string {
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
            if (isset($match['constraint'])) {
                $constraint = $match['constraint'];

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
                $constraints[$match['parameter']] = $match['constraint'];

                return ':' . $match['parameter'];
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

                $this->data('view', $render);
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
