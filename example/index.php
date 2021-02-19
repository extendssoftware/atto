<?php
declare(strict_types=1);

require __DIR__ . '/../dist/atto-1.0.0.php';

echo (new AttoPHP())
    ->start(function() {
        $this->data('meta.start', microtime(true));
    })
    ->root(__DIR__ . '/templates/')
    ->layout('layout.phtml')
    ->route('home', '/', 'home.phtml')
    ->route('blog', '/blog[/:page<\d+>]', 'blog.phtml', function (string $page = null): void {
        if ($page === '1') {
            $this->redirect('blog');
            exit;
        }

        $this
            ->data('blog.posts', [])
            ->data('blog.page', $page ?: '1');
    })
    ->route('blog-post', '/blog/:slug<[a-z\-]+>', 'blog-post.phtml', function (): void {
        $this->data('blog.post', []);
    })
    ->route('contact', '/contact', null, function(): void {
        $this
            ->data('layout.title', 'Contact')
            ->data('layout.description', 'Fancy contact description.')
            ->view('contact.phtml');
    })
    ->route('404', '/404', '404.phtml')
    ->route('catch-all', '/*', null, function (): void {
        $this->redirect('404');
        exit;
    })
    ->finish(function(string $render): string {
        return sprintf(
            '%s<!-- %.6f seconds -->',
            $render,
            microtime(true) - $this->data('meta.start')
        );
    })
    ->run();