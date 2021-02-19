# AttoPHP

[![License](https://poser.pugx.org/extendssoftware/atto-php/license)](https://packagist.org/packages/extendssoftware/atto-php)
[![Latest Stable Version](https://poser.pugx.org/extendssoftware/atto-php/v/stable)](https://packagist.org/packages/extendssoftware/atto-php)
[![Total Downloads](https://poser.pugx.org/extendssoftware/atto-php/downloads)](https://packagist.org/packages/extendssoftwarr/atto-php)

AttoPHP is a tool based on the [builder pattern](https://en.wikipedia.org/wiki/Builder_pattern) to configure, route and
render a website with ease.

- [1. Introduction](#1-introduction)
- [2. Requirements](#2-requirements)
- [3. Installation](#3-installation)
- [4. Features](#4-features)
- [5. Usage](#5-usage)
    - [5.1 Routes](#51-routes)
        - [5.1. Name](#511-name)
        - [5.1.1 Pattern match and assemble](#512-pattern-match-and-assemble)
        - [5.1.2 Constraints and HTTP methods](#513-constraints-and-http-methods)
        - [5.1.3 View and callback](#514-view-and-callback)
        - [5.1.4 Examples](#515-examples)
    - [5.2 Templates](#52-templates)
        - [5.2. Template directory](#521-template-directory)
        - [5.2. Inline template](#522-inline-templates)
        - [5.2. Layout and view](#523-layout-and-view)
    - [5.3 Data container](#53-data-container)
    - [5.4 Callbacks](#54-callbacks)
        - [5.4.1 Start](#541-start)
        - [5.4.1 Route](#542-route)
        - [5.4.1 Finish](#543-finish)
        - [5.4.1 Error](#544-error)
- [6. Control flow](#6-control-flow)
- [7. Error handling](#7-error-handling)
- [8. Example](#8-example)

## 1. Introduction

There are lots of other solutions out there, like [Slim Framework](https://github.com/slimphp/Slim),
[bramus/router](https://github.com/bramus/router), [Twig](https://github.com/twigphp/Twig) and many others. Each with
their own specialties, dependencies and all recommend installing with Composer.

What if you want a simple website, fast but with nice SEO URLs and PHP templating without the skills of a programmer?
AttoPHP can be the right choice for you. Small, no dependencies and available in a single file without a namespace to
use easily over FTP.

AttoPHP is not as comprehensive as the others, but it is fast and simple to use. Once you understand some core
principles, you are good to go. Some very basic PHP knowledge is preferred, but if you can read and modify some simple
PHP, you will come to an end.

## 2. Requirements

- PHP ^7.4 || ^8.0
- [URL rewriting](https://en.wikipedia.org/wiki/Rewrite_engine)

## 3. Installation

It is very easy to install AttoPHP with [Composer](https://getcomposer.org/):

```
$ composer require extendssoftware/atto-php
```

If you want to use AttoPHP as a single PHP file, copy the files from the
[dist](https://github.com/extendssoftware/atto-php/releases/tag/1.0.0) directory in the web root of your site. Or
download the files to a local directory and run the following command to see AttoPHP in action:

```
$ php -S localhost:8000
```

The [dist](https://github.com/extendssoftware/atto-php/tree/1.0.0/dist) directory also contains
[index.php](https://github.com/extendssoftware/atto-php/tree/1.0.0/dist/index.php), with some basic routes, and some
templates. It is recommended to place the AttoPHP PHP file and templates outside the web root.

## 4. Features

Not that much, but just enough to get your site started:

- Match and assemble routes
- Redirect to URL and route
- Callbacks on start, route, finish and error
- Render PHP templates: layout, view and partial
- Data container

In every callback and template AttoPHP is the current object ```$this```, so whatever you are doing, you can use these
features.

Everybody familiar with [jQuery](https://jquery.com/) knows how a combined get/set method works. Let's consider the
```view``` method. When this method is called without argument, the current set view will be returned, or null when no
view set. When this method is called with a view filename, the view will be set. Normally, when using proper
[OOP](https://en.wikipedia.org/wiki/Object-oriented_programming), this will be two methods, ```getView``` and
```setView```. AttoPHP uses combined methods to keep it compact and fast.

## 5 Usage

After everything is set up, the method ```run``` needs to be called to run AttoPHP and get the rendered content back.

### 5.1 Routes

It all begins with matching a URL to a view and/or callback. A route can have the following properties:

- Name
- Pattern
- View
- Callback

#### 5.1.1 Name

The route name can be anything you like. The route name is required and used to the get the route during assembling, for
example in a template. With the option to assembles routes, there is no need to change a URL in every place of the
website. Just change the URL and it will change everywhere as long as you keep the name the same.

#### 5.1.2 Pattern (match and assemble)

The pattern will be used to check if the route matches the URL. Route matching is done for the path of the URL,
everything behind the top-level domain (TLD), and without the query string ```/foo/bar/baz```.

The pattern can consist of static text, parameters and optional parts. A required parameter starts with a colon followed
by an alphabetical character and can consist of alphanumeric characters, and an underscore ```/blog/:blogId```.
Parameters outside optional parts are required and must be present in the URL in order for the route to match.

Optional URL parts are surrounded with square brackets ```/blog[/:page]``` and can be nested
```/blog/:blogId[/comments[/:page]]```. Parameters inside an optional part are not required. An optional part will only
match when all the parameters inside the part are matched. Optional parts processes from the outside in. For the
pattern ```/blog/:blogId[/comments[/:page]]``` the part with page will only match when the page is specified in the URL.
The comments part will match with or without the page part.

This also applies to route assembly. An optional part will only assemble when all the parameters are specified and when
every nested optional part is also assembled. The route part with the parameter baz from the route
```/foo[/:bar[/:baz]]``` will only assemble when the parameter bar is also specified.

#### 5.1.3 Constraints and HTTP methods

Constraints can be specified for route parameters. A constraint is a
[regular expression](https://en.wikipedia.org/wiki/Regular_expression) without the delimiters ```[a-z0-9-]+```. A
constraint must be added after the parameter name between the ```<``` and ```>``` characters and contain the regular
expression ```/blog/:page<\d+>```. A route will not match when the parameter value does not match the constraint. When
no constraint is specified, the default constraint is ```[^/]+```, match everything till the next forward slash, or the
end of the URL if there is none left.

An asterisk ```*``` can be used in a route pattern to match all characters in the URL. The route ```/foo/*``` will match
```/foo/bar```, but wil also match ```/foo/baz```. A route like ```/*``` will match any URL. An asterisk matches zero to
unlimited characters. It is recommended to add a catch-all route ```/*``` as last route. This route can be used to
redirect to a proper 404 page or show a 404 page for the current URL.

When assembling a route, an error will occur when the constraint does not match the parameter value. The letter ```a```
is not allowed when the constraint only allows digits ```\d+```.

[HTTP methods](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Request_methods) can be added to the beginning
of the route pattern, must be divided by a pipe and have a trailing whitespace character
```POST|DELETE /blog/:blogId```. When no HTTP method is specified, all methods will match the route. The HTTP method for
a route is not included during route assembly. An HTTP method can also be used for a route with an asterisk.

#### 5.1.4 View and callback

The view for a route is optional. When a route matches and has a view set, this view will be set to AttoPHP for later
use.

When you want any of the URL parameters available in the callback, you need to add callback arguments matching the exact
same name as the route parameters. The value will be of type string. When a route parameter is optional, the argument
must have a default value or allow null. For the parameter ```:blogId``` you have to add the argument ```$blogId```. The
order of the arguments does not matter.

To get the matched route in the callback or a template, the method ```route``` must be called without any arguments. The
matched route also contains all the matched URL parameters.

#### 5.1.5 Examples

```
/blog[/:page<\d+>]
```

The URLs ```/blog``` and ```/blog/1``` are allowed. The URL ```/blog/a``` will not match because the constraint only
allows digits. Page parameter is optional. Will match any HTTP method.

```
POST|DELETE /blog/:blogId
```

The HTTP methods ```POST``` and ```DELETE``` are allowed for this route. ```blogId``` will match everything except a
forward slash. It is considered a good practise to always add a constraint to avoid strange behavior and URLs for SEO.

```
/*
```

Catch-all route, will match any URL for any HTTP method.

```
/blog/*.html
```

Will match any route that begins with ```/blog/``` and ends with ```.html```.

### 5.2 Templates

PHP include is used to render a template with the ```render``` method. AttoPHP is set as the current object ```$this```
for the template to render. AttoPHP calls this method for the layout and view, when set. When you call the ```render```
method manually, you have to specify the current object for the template. When you render a template from a template or
a callback, you can pass ```$this``` as the second parameter or your own object.

#### 5.2.1 Template directory

To always set the full path to a template can be cumbersome, to counter this, the method ```root``` can be called with a
path to the template directory. When a template is rendered, AttoPHP will check if the specified template is a file. If
so, the template will be rendered and returned. When it is not a file and a template root directory is set, the
directory is checked if the file exists. If so, render that file.

#### 5.2.2 Inline templates

A template is considered an inline template when no file can be found at the absolute or relative path. This can be
useful when templates are stored in a database. With this method PHP rendering is not available, and the template will
be directly returned by the ```render``` method. If you want to parse this template, you can use a template parser in
combination with the ```render``` method.

#### 5.2.3 Layout and view

AttoPHP has methods to set a layout and a view, ```layout``` and ```view``` respectively. If set, the view will be
rendered before the layout is, if set. The rendered view will be available in the layout as the data container path
```atto.view```.

### 5.3 Data container

AttoPHP has a data container which can be called with the method ```data```. Any data you like can be set to the data
container and used in every callback and template. The path to set the data for must use dot notation with a colon, dot
or forward slash as separator. They can be used interchangeably, but it is not recommended. The characters between the
separator can only consist of a-z and 0-9, case-insensitive.

Keep in mind that every separator makes the following key a nested value for the previous key. The value for path
```foo.bar.baz``` will be overwritten when a value for path ```foo.bar``` will be set. When a value for
```foo.bar.qux``` is set, it will be added next to ```baz```.

The advantage of dot notation is the grouping of data. For example, you can group all the data for the layout,
```layout.title``` for the title and ```layout.description``` for the description.

### 5.4 Callbacks

If a callback returns a [truly value](https://www.php.net/manual/en/function.boolval.php), this value will be directly
returned by the AttoPHP method ```run``` and execution is stopped afterwards.

The current object ```$this``` for a callback is the AttoPHP class. All the functionality AttoPHP provides is available
in the callback.

#### 5.4.1 Start

This callback is called before routing begins and has no arguments.

#### 5.4.2 Route

This callback is called when a route is matched and has a callback specified. The route parameters can be specified as
arguments. For example, the parameter ```:blogId``` will be available as the argument ```$blogId```.

#### 5.4.3 Finish

This callback is called after the layout and/or view are rendered. The rendered content will be available as argument
with the name ```$render```.

#### 5.4.4 Error

This callback is called when an error occurs. The occurred error is available as argument with the name
```$throwable```.

## 6 Control flow

To get a basic idea of how AttoPHP works the [happy path](https://en.wikipedia.org/wiki/Happy_path) is explained here:

- If set, call start callback
    - If truly return value, return value and stop execution
- Find a matching route, if found:
    - Set matched route to Atto
    - If set, set the view to Atto
    - If set, call the route callback
        - If truly return value, return value and stop execution
- If set, render view
    - Set rendered content to data container with path ```atto.view```
- If set, render layout
- If set, call finish callback
    - If truly return value, return value and stop execution
- Return rendered content

On error:

- If set, call error callback
    - If truly return value, return value and stop execution
    - If callback error, return error message and stop execution
- Return error message

## 7 Error handling

AttoPHP catches all the errors that occur while running. If there is no callback error, or the callback doesn't return a
truly value, the error message from the original error will be returned. If the error occurred while rendering a
template, the output if cleaned before the error will be returned. So, an error wil never show deeply nested inside an
HTML element.

## 8 Example

Take a look in the [dist](https://github.com/extendssoftware/atto-php/tree/1.0.0/dist) directory for a working example.