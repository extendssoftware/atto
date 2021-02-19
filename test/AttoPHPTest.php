<?php
declare(strict_types=1);

namespace ExtendsSoftware\AttoPHP;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use function xdebug_get_headers;

/**
 * Test of class AttoPHP.
 *
 * @package ExtendsSoftware\AttoPHP
 * @author  Vincent van Dijk <vincent@extends.nl>
 * @version 1.0.0
 * @see     https://github.com/extendssoftware/atto-php
 */
class AttoPHPTest extends TestCase
{

    /**
     * Test get/set start callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::start()
     */
    public function testStartCallback(): void
    {
        $closure = static function () {
        };

        $atto = new AttoPHP();
        $atto->start($closure);

        self::assertSame($closure, $atto->start());
    }

    /**
     * Test get/set finish callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::finish()
     */
    public function testFinishCallback(): void
    {
        $closure = static function () {
        };

        $atto = new AttoPHP();
        $atto->finish($closure);

        self::assertSame($closure, $atto->finish());
    }

    /**
     * Test get/set error callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::error()
     */
    public function testErrorCallback(): void
    {
        $closure = static function () {
        };

        $atto = new AttoPHP();
        $atto->error($closure);

        self::assertSame($closure, $atto->error());
    }

    /**
     * Test get/set root.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::root()
     */
    public function testRoot(): void
    {
        $atto = new AttoPHP();
        self::assertNull($atto->root());

        $atto->root('./render');
        self::assertSame('./render', $atto->root());
    }

    /**
     * Test get/set view.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::view()
     */
    public function testView(): void
    {
        $atto = new AttoPHP();
        self::assertNull($atto->view());

        $atto->view('./view.phtml');
        self::assertSame('./view.phtml', $atto->view());
    }

    /**
     * Test get/set layout.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::layout()
     */
    public function testLayout(): void
    {
        $atto = new AttoPHP();
        self::assertNull($atto->layout());

        $atto->layout('./layout.phtml');
        self::assertSame('./layout.phtml', $atto->layout());
    }

    /**
     * Test get/set data.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::data()
     */
    public function testData(): void
    {
        $atto = new AttoPHP();
        $atto
            ->data('layout.title', 'New website!')
            ->data('layout.description', 'Fancy description.');

        self::assertSame('New website!', $atto->data('layout.title'));
        self::assertNull($atto->data('blog.title'));
        self::assertNull($atto->data('layout.title.first'));
        self::assertSame([
            'layout' => [
                'title' => 'New website!',
                'description' => 'Fancy description.',
            ],
        ], $atto->data());
    }

    /**
     * Test get/set data with invalid path notation.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::data()
     */
    public function testDataInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path ".path.to.set" is not a valid dot notation. Please fix the notation. The ' .
            'colon (:), dot (.) and slash (/) characters can be used as separator. The can be used interchangeably. ' .
            'The characters between the separator can only consist of a-z and 0-9, case insensitive.');

        $atto = new AttoPHP();
        $atto->data('.path.to.set');
    }

    /**
     * Test get/set route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::route()
     */
    public function testRoute(): void
    {
        $closure = static function () {
        };

        $atto = new AttoPHP();
        $atto
            ->route('blog', '/blog')
            ->route('blog-post', '/blog/:subject', './blog-post.phtml', $closure);

        self::assertSame([
            'name' => 'blog',
            'pattern' => '/blog',
            'view' => null,
            'callback' => null,
        ], $atto->route('blog'));

        self::assertSame([
            'name' => 'blog-post',
            'pattern' => '/blog/:subject',
            'view' => './blog-post.phtml',
            'callback' => $closure,
        ], $atto->route('blog-post'));

        self::assertNull($atto->route('home'));
    }

    /**
     * Test redirect to URL.
     *
     * @runInSeparateProcess
     * @covers       \ExtendsSoftware\AttoPHP\AttoPHP::redirect()
     * @throws Throwable
     * @noinspection ForgottenDebugOutputInspection
     */
    public function testRedirectToUrl(): void
    {
        $atto = new AttoPHP();
        $atto->redirect('/blog');

        self::assertContains('Location: /blog', xdebug_get_headers());
    }

    /**
     * Test redirect to route.
     *
     * @runInSeparateProcess
     * @covers       \ExtendsSoftware\AttoPHP\AttoPHP::redirect()
     * @throws Throwable
     * @noinspection ForgottenDebugOutputInspection
     */
    public function testRedirectToRoute(): void
    {
        $atto = new AttoPHP();
        $atto
            ->route('blog-post', '/blog/:slug')
            ->redirect('blog-post', ['slug' => 'new-post']);

        self::assertContains('Location: /blog/new-post', xdebug_get_headers());
    }

    /**
     * Test assemble static URL.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleStaticUrl(): void
    {
        $atto = new AttoPHP();
        $atto->route('contact', '/contact');

        self::assertSame('/contact', $atto->assemble('contact'));
    }

    /**
     * Test assemble with query string.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleQueryString(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog[/:page]');

        self::assertSame('/blog/3?sort=desc', $atto->assemble('blog', ['page' => 3], ['sort' => 'desc']));
    }

    /**
     * Test assemble with required parameter.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleRequiredParameter(): void
    {
        $atto = new AttoPHP();
        $atto->route('help', '/help/:subject');

        self::assertSame('/help/create-new-post', $atto->assemble('help', ['subject' => 'create-new-post']));
    }

    /**
     * Test assemble with optional parameters.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleOptionalParameter(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog-post', '/blog[/:slug<[a-z-]+>[/comments/:page<\d+>]]');

        self::assertSame('/blog/new-post', $atto->assemble('blog-post', ['slug' => 'new-post']));
        self::assertSame('/blog/new-post/comments/4', $atto->assemble('blog-post', ['slug' => 'new-post', 'page' => 4]));
    }

    /**
     * Test assemble parameter with valid constraint value.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleValidParameterValue(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog/:page<\d+>');

        self::assertSame('/blog/4', $atto->assemble('blog', ['page' => '4']));
    }

    /**
     * Test assemble asterisk route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleAsteriskRoute(): void
    {
        $atto = new AttoPHP();
        $atto->route('asterisk', '/foo*');
        static::assertSame('/foo', $atto->assemble('asterisk'));
    }

    /**
     * Test assemble required parameter with invalid constraint value.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleInvalidRequiredParameterValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value "a" for parameter "page" is not allowed by constraint "[\d]+" for route ' .
            'with name "blog". Please give a valid value.');

        $atto = new AttoPHP();
        $atto
            ->route('blog', '/blog/:page<[\d]+>')
            ->assemble('blog', ['page' => 'a']);
    }

    /**
     * Test assemble optional parameter with invalid constraint value.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleInvalidOptionalParameterValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value "a" for parameter "page" is not allowed by constraint "\d+" for route ' .
            'with name "blog". Please give a valid value.');

        $atto = new AttoPHP();
        $atto
            ->route('blog', '/blog[/:page<\d+>]')
            ->assemble('blog', ['page' => 'a']);
    }

    /**
     * Test assemble with missing required parameter.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleMissingRequiredParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required parameter "subject" for route name "help" is missing. Please give ' .
            'the required parameter or change the route URL.');

        $atto = new AttoPHP();
        $atto
            ->route('help', '/help/:subject')
            ->assemble('help');
    }

    /**
     * Test assemble non-existing route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::assemble()
     * @throws Throwable
     */
    public function testAssembleNonExistingRoute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No route found with name "help". Please check the name of the route or give ' .
            'a new route with the same name.');

        $atto = new AttoPHP();
        $atto->assemble('help');
    }

    /**
     * Test match URL path to route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchStaticUrl(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', 'GET|POST /blog');

        self::assertSame('blog', $atto->match('/blog', 'GET')['name']);
    }

    /**
     * Test match URL path to route with required parameter.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchRequiredParameter(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog/:page');

        self::assertSame('blog', $atto->match('/blog/4', 'GET')['name']);
    }

    /**
     * Test match URL path to route with optional parameters.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchOptionalParameters(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog-post', '/blog/:slug[/comments[/:page]]');

        self::assertSame('blog-post', $atto->match('/blog/new-post', 'GET')['name']);
        self::assertSame('blog-post', $atto->match('/blog/new-post/comments', 'GET')['name']);
        self::assertSame('blog-post', $atto->match('/blog/new-post/comments/4', 'GET')['name']);
    }

    /**
     * Test match URL path to required and optional parameter with constraints.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchParameterConstraint(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog/:slug<[a-z\-]+>[/comments/:page<\d+>]');

        self::assertSame('blog', $atto->match('/blog/foo-bar', 'GET')['name']);
        self::assertSame('blog', $atto->match('/blog/foo-bar/comments/4', 'GET')['name']);
        self::assertNull($atto->match('/blog/foo+bar/comments/4', 'GET'));
        self::assertNull($atto->match('/blog/foo-bar/comments/4a', 'GET'));
    }

    /**
     * Test match URL path to route with asterisk.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchAsterisk(): void
    {
        $atto = new AttoPHP();
        $atto
            ->route('foo', '/foo*')
            ->route('bar', '/bar/*/foo')
            ->route('baz', '*/baz')
            ->route('catch-all', '*');

        self::assertSame('catch-all', $atto->match('/blog/new-post', 'GET')['name']);
        self::assertSame('catch-all', $atto->match('/help/create-new-post', 'GET')['name']);
        self::assertSame('foo', $atto->match('/foo/bar/baz', 'GET')['name']);
        self::assertSame('bar', $atto->match('/bar/baz/foo', 'GET')['name']);
        self::assertSame('baz', $atto->match('/bar/foo/baz', 'GET')['name']);
    }

    /**
     * Test match URL path to no route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchNoMatch(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog');

        self::assertNull($atto->match('/blog/new-post', 'GET'));
    }

    /**
     * Test match URL path to no route.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     */
    public function testMatchRequestMethods(): void
    {
        $atto = new AttoPHP();
        $atto
            ->route('blog', 'POST|DELETE /blog')
            ->route('blog-post', '/blog/:slug');

        self::assertNull($atto->match('/blog', 'GET'));

        self::assertSame('blog-post', $atto->match('/blog/foo-bar', 'POST')['name']);
        self::assertSame('blog', $atto->match('/blog', 'POST')['name']);
        self::assertSame('blog', $atto->match('/blog', 'DELETE')['name']);
    }

    /**
     * Test render file.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::render()
     * @throws Throwable
     */
    public function testRenderFile(): void
    {
        $atto = new AttoPHP();
        $atto->data('title', 'Homepage');

        self::assertSame('<h1>Homepage</h1>', $atto->render(__DIR__ . '/templates/view.phtml'));
    }

    /**
     * Test render file with root set.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::render()
     * @throws Throwable
     */
    public function testRenderFileWithRoot(): void
    {
        $atto = new AttoPHP();
        $atto
            ->root(__DIR__ . '/templates/')
            ->data('title', 'Homepage');

        self::assertSame('<h1>Homepage</h1>', $atto->render('view.phtml'));
    }

    /**
     * Test render file.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::render()
     * @throws Throwable
     */
    public function testRenderString(): void
    {
        $atto = new AttoPHP();

        self::assertSame('<h1>Homepage</h1>', $atto->render('<h1>Homepage</h1>'));
    }

    /**
     * Test render throwable inside included file.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::render()
     * @throws Throwable
     */
    public function testRenderCallbackThrowable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Thrown inside included file.');

        $atto = new AttoPHP();
        $atto->render(__DIR__ . '/templates/throwable.phtml');
    }

    /**
     * Test closure call.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::call()
     * @throws Throwable
     */
    public function testCall(): void
    {
        $newThis = $this;
        $closure = function (string $arg1, ?int $arg2, string $arg3 = 'foo', string $arg4 = null) use ($newThis) {
            $newThis::assertSame($this, $newThis);
            $newThis::assertSame('foo', $arg1);
            $newThis::assertNull($arg2);
            $newThis::assertSame('foo', $arg3);
            $newThis::assertNull($arg4);
        };

        $atto = new AttoPHP();
        $atto->call($closure, $newThis, [
            'arg1' => 'foo',
        ]);
    }

    /**
     * Test closure call with missing required argument.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::call()
     * @throws Throwable
     */
    public function testCallRequiredArgumentMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required argument "arg1" for callback is not provided in the arguments array, ' .
            'does not has a default value and is not nullable. Please give the missing argument or give it a default ' .
            'value.');

        $closure = static function (string $arg1) {
        };

        $atto = new AttoPHP();
        $atto->call($closure, $this);
    }

    /**
     * Test run with expected flow.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRun(): void
    {
        $atto = new AttoPHP();
        $atto
            ->layout(__DIR__ . '/templates/layout.phtml')
            ->route('blog', '/blog', __DIR__ . '/templates/view.phtml', function () {
                /** @var AttoPHPInterface $this */
                $this->data('title', 'Homepage');
            });

        static::assertSame('<div><h1>Homepage</h1></div>', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run with route view overwriting earlier set view.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunViewOverwrite(): void
    {
        $atto = new AttoPHP();
        $atto
            ->view(__DIR__ . '/templates/throwable.phtml')
            ->layout(__DIR__ . '/templates/layout.phtml')
            ->route('blog', '/blog', __DIR__ . '/templates/view.phtml', function () {
                /** @var AttoPHPInterface $this */
                $this->data('title', 'Homepage');
            });

        static::assertSame('<div><h1>Homepage</h1></div>', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run with short circuit from start callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromStartCallback(): void
    {
        $atto = new AttoPHP();
        $atto->start(function () {
            return 'short circuit';
        });

        static::assertSame('short circuit', $atto->run('/', 'GET'));
    }

    /**
     * Test run with short circuit from finish callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromFinishCallback(): void
    {
        $atto = new AttoPHP();
        $atto->finish(function () {
            return 'short circuit';
        });

        static::assertSame('short circuit', $atto->run('/', 'GET'));
    }

    /**
     * Test run with short circuit from error callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromErrorCallback(): void
    {
        $atto = new AttoPHP();
        $atto
            ->error(function (Throwable $throwable) {
                return $throwable->getMessage() . ' 2';
            })
            ->route('blog', '/blog', null, function () {
                throw new RuntimeException('short circuit');
            });

        static::assertSame('short circuit 2', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run with caught Throwable from route callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromCaughtException(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog', null, function () {
            throw new RuntimeException('short circuit');
        });

        static::assertSame('short circuit', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run with caught Throwable from error callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromCaughtErrorCallback(): void
    {
        $atto = new AttoPHP();
        $atto
            ->error(function () {
                throw new RuntimeException('short circuit 2');
            })
            ->route('blog', '/blog', null, function () {
                throw new RuntimeException('short circuit 1');
            });

        static::assertSame('short circuit 2', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run with short circuit from matched route callback.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::run()
     */
    public function testRunWithReturnFromRoute(): void
    {
        $atto = new AttoPHP();
        $atto->route('blog', '/blog', null, function () {
            return 'short circuit';
        });

        static::assertSame('short circuit', $atto->run('/blog', 'GET'));
    }

    /**
     * Test run and matched route will be returned.
     *
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::match()
     * @covers \ExtendsSoftware\AttoPHP\AttoPHP::route()
     */
    public function testRunMatchedRoute(): void
    {
        $atto = new AttoPHP();
        $atto
            ->route('blog', '/blog')
            ->run('/blog', 'GET');

        self::assertSame('blog', $atto->route()['name']);
    }
}
