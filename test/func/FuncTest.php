<?php

namespace Dhii\Functions\FuncTest;

use BadFunctionCallException;
use Countable;
use Dhii\Functions\Func;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;

/**
 * Functional tests for {@link Func}.
 *
 * @since [*next-version*]
 * @see   Func
 */
class FuncTest extends TestCase
{
    /**
     * Tests that applying a wrapped functions does not modify that original wrapped function.
     *
     * A wrapped function is created for a callable (by applying it without any args), and a second wrapper is created
     * for the first, with pre-applied arguments. This second wrapper is ignored and the first one is tested instead.
     *
     * The test ensures that the first wrapped function is completely agnostic that is has been wrapped by another,
     * thus verifying that the second wrapper is in fact a complete copy.
     *
     * @since [*next-version*]
     */
    public function testCopyOnApply()
    {
        $callable = function ($arg1) {
            return $arg1;
        };

        $func = Func::apply($callable, []);
        Func::apply($func, ['B']);

        static::assertEquals('A', $func('A'));
    }

    /**
     * @since [*next-version*]
     */
    public function testNoop()
    {
        $func = Func::noop();

        static::assertIsCallable($func);
        static::assertNull($func());
    }

    /**
     * @since [*next-version*]
     */
    public function testThatReturns()
    {
        $func = Func::thatReturns(88);

        static::assertEquals(88, $func());
    }

    /**
     * @since [*next-version*]
     */
    public function testApplyArgs()
    {
        $callable = function (string $arg, int $num) {
            return $arg . str_repeat('!', $num);
        };

        $func = Func::apply($callable, ['test', 3]);

        static::assertEquals('test!!!', $func());
    }

    /**
     * @since [*next-version*]
     */
    public function testApplyAndSkipArgs()
    {
        $callable = function (string $arg, int $num) {
            return $arg . str_repeat('!', $num);
        };

        $func = Func::apply($callable, [Func::SKIP, 3]);

        static::assertEquals('test!!!', $func('test'));
    }

    /**
     * @since [*next-version*]
     */
    public function testApplyArgsTwice()
    {
        $callable = function ($name, $surname) {
            return $name . ' ' . $surname;
        };
        $func1 = Func::apply($callable, ['Bob']);
        $func2 = Func::apply($func1, ['Page']);

        static::assertEquals('Bob Page', $func2());
    }

    /**
     * @since [*next-version*]
     */
    public function testApplyAndSKipArgsTwice()
    {
        $callable = function ($name, $surname, $title, $nickname) {
            return "{$title}. {$name} {$surname} - ${nickname}";
        };
        $func1 = Func::apply($callable, [Func::SKIP, 'Anderson', Func::SKIP, 'Neo']);
        $func2 = Func::apply($func1, [Func::SKIP, 'Mr']);
        $func3 = Func::apply($func2, ['Thomas']);

        static::assertEquals('Mr. Thomas Anderson - Neo', $func3());
    }

    /**
     * @since [*next-version*]
     */
    public function testApplyArgsAndCallWithArgs()
    {
        $callable = function ($name, $surname) {
            return $name . ' ' . $surname;
        };
        $func = Func::apply($callable, ['Bob']);

        static::assertEquals('Bob Page', $func('Page'));
    }

    /**
     * @since [*next-version*]
     */
    public function testMerge()
    {
        $counter = 5;

        $callable1 = function () use (&$counter) {
            $counter *= 2;

            return 5;
        };
        $callable2 = function () use (&$counter) {
            $counter++;

            return 4;
        };

        $func = Func::merge([
            $callable1,
            $callable2,
        ]);

        static::assertNull($func());
        static::assertEquals(11, $counter);
    }

    /**
     * @since [*next-version*]
     */
    public function testMergeWithPreAppliedArgs()
    {
        $counter = 5;

        $callable1 = function ($arg) use (&$counter) {
            $counter *= $arg;
        };
        $callable2 = function ($arg) use (&$counter) {
            $counter += $arg;
        };

        $func1 = Func::apply($callable1, [2]);
        $func2 = Func::apply($callable2, [5]);

        $func = Func::merge([
            $func1,
            $func2,
        ]);

        static::assertNull($func());
        static::assertEquals(15, $counter);
    }

    /**
     * @since [*next-version*]
     */
    public function testPipe()
    {
        $callable1 = function ($arg) {
            return $arg . ' and ';
        };
        $callable2 = function ($arg) {
            return $arg . 'copper';
        };

        $func = Func::pipe([
            $callable1,
            $callable2,
        ]);

        static::assertEquals("Iron and copper", $func("Iron"));
    }

    /**
     * @since [*next-version*]
     */
    public function testPipeWithPreAppliedArgs()
    {
        $callable1 = function ($str, int $n) {
            return $str . str_repeat('!', $n);
        };
        $callable2 = function ($arg, $title) {
            return $title . ' ' . $arg;
        };

        $func1 = Func::apply($callable1, [Func::SKIP, 3]);
        $func2 = Func::apply($callable2, [Func::SKIP, 'Mr.']);

        $func = Func::pipe([
            $func1,
            $func2,
        ]);

        static::assertEquals("Mr. Anderson!!!", $func("Anderson"));
    }

    /**
     * @since [*next-version*]
     */
    public function testCatch()
    {
        $callable = function () {
            throw new OutOfBoundsException();
        };

        $func = Func::catch($callable, [OutOfBoundsException::class], function () {
            return 55;
        });

        static::assertEquals(55, $func());
    }

    /**
     * @since [*next-version*]
     */
    public function testCatchBubble()
    {
        $callable = function () {
            throw new RuntimeException();
        };

        $func = Func::catch($callable, [OutOfBoundsException::class], function () {
            return 55;
        });

        static::expectException(RuntimeException::class);
        $func();
    }

    /**
     * @since [*next-version*]
     */
    public function testCatchHasExceptionArg()
    {
        $exception = new OutOfBoundsException();

        $callable = function () use ($exception) {
            throw $exception;
        };

        $catch = function ($argException) use ($exception) {
            static::assertSame($argException, $exception);
        };

        $func = Func::catch($callable, [OutOfBoundsException::class], $catch);

        $func();
    }

    /**
     * @since [*next-version*]
     */
    public function testCatchWithArgs()
    {
        $callable = function (string $key) {
            throw new OutOfBoundsException();
        };

        $catch = function ($exception, $key) {
            return $key;
        };

        $func = Func::catch($callable, [OutOfBoundsException::class], $catch);

        static::assertEquals("foobar", $func("foobar"));
    }

    /**
     * @since [*next-version*]
     */
    public function testMemoize()
    {
        $counter = 0;

        $callable = function ($x, $y) use (&$counter) {
            $counter++;

            return $x + $y;
        };

        $func = Func::memoize($callable);

        static::assertEquals(99, $func(90, 9));
        static::assertEquals(99, $func(90, 9));
        static::assertEquals(99, $func(90, 9));
        static::assertEquals(1, $counter);
    }

    /**
     * @since [*next-version*]
     */
    public function testMemoizeDifferentArgs()
    {
        $counter = 0;

        $callable = function ($x, $y) use (&$counter) {
            $counter++;

            return $x + $y;
        };

        $func = Func::memoize($callable);

        static::assertEquals(99, $func(90, 9));
        static::assertEquals(20, $func(15, 5));
        static::assertEquals(20, $func(15, 5));
        static::assertEquals(99, $func(90, 9));
        static::assertEquals(0, $func(0, 0));
        static::assertEquals(3, $counter);
    }

    /**
     * Tests different functions with the same arguments to ensure that different functions don't share the same cache.
     *
     * @since [*next-version*]
     */
    public function testMemoizeDifferentFuncs()
    {
        $callable1 = function ($x, $y) {
            return $x + $y;
        };
        $callable2 = function ($x, $y) {
            return $x * $y;
        };

        $func1 = Func::memoize($callable1);
        $func2 = Func::memoize($callable2);

        static::assertEquals(10, $func1(2, 8));
        static::assertEquals(16, $func2(2, 8));
        static::assertEquals(10, $func1(2, 8));
        static::assertEquals(16, $func2(2, 8));
    }

    /**
     * @since [*next-version*]
     */
    public function testMemoizeClosureArgs()
    {
        $counter = 0;

        $callable = function (callable $callback) use (&$counter) {
            $counter++;

            return $callback();
        };
        $callback1 = function () {
        };
        $callback2 = function () {
        };

        $func = Func::memoize($callable);

        $func($callback1);
        $func($callback1);
        $func($callback1);

        $func($callback2);
        $func($callback2);
        $func($callback2);

        static::assertEquals(2, $counter);
    }

    /**
     * @since [*next-version*]
     */
    public function testMapArgs()
    {
        $callable = function (int $a, $b) {
            return $a + $b;
        };

        $func = Func::mapArgs($callable, function ($arg) {
            return strlen((string) $arg);
        });

        static::assertEquals(10, $func("hello", "world"));
    }

    /**
     * @since [*next-version*]
     */
    public function testReorderArgs()
    {
        $callable = function ($x, $y) {
            return $x / $y;
        };

        $func = Func::reorderArgs($callable, [0 => 1]);

        static::assertEquals(12, $func(3, 36));
    }

    /**
     * @since [*next-version*]
     */
    public function testReorderArgsWithNoArgs()
    {
        $callable = function () {
            return 7;
        };

        $func = Func::reorderArgs($callable, [0 => 1, 3 => 1]);

        static::assertEquals(7, $func());
    }

    /**
     * @since [*next-version*]
     */
    public function testReorderArgsWithNoReordering()
    {
        $callable = function ($a, $b, $c) {
            return "$a $b $c";
        };

        $func = Func::reorderArgs($callable, []);

        static::assertEquals('a b c', $func('a', 'b', 'c'));
    }

    /**
     * Tests the invalid scenario documented in {@link Func::reorderArgs()}'s method doc.
     *
     * @since [*next-version*]
     */
    public function testReorderArgsDocExampleInvalid()
    {
        $callable = function ($a, $b, $c, $d) {
            return "$a $b $c $d";
        };

        $func = Func::reorderArgs($callable, [0 => 2, 2 => 1]);

        static::assertEquals("a d c b", $func('d', 'a', 'c', 'b'));
    }

    /**
     * Tests the valid scenario documented in {@link Func::reorderArgs()}'s method doc.
     *
     * @since [*next-version*]
     */
    public function testReorderArgsDocExampleValid()
    {
        $callable = function ($a, $b, $c, $d) {
            return "$a $b $c $d";
        };

        $func = Func::reorderArgs($callable, [0 => 2, 3 => 1]);

        static::assertEquals("a b c d", $func('d', 'a', 'c', 'b'));
    }

    /**
     * @since [*next-version*]
     */
    public function testCapture()
    {
        $callable = function ($name, $title) {
            echo "Hello $title. $name";
        };

        $func = Func::capture($callable);

        static::assertEquals("Hello Mr. Anderson", $func("Anderson", "Mr"));
    }

    /**
     * @since [*next-version*]
     */
    public function testOutput()
    {
        $callable = function ($name, $title) {
            return "Hello $title. $name";
        };

        $func = Func::output($callable);

        static::expectOutputString("Hello Mr. Anderson");
        $func("Anderson", "Mr");
    }

    /**
     * @since [*next-version*]
     *
     * @throws ReflectionException
     */
    public function testMethod()
    {
        $mock = $this->getMockForAbstractClass(Countable::class);
        $mock->expects(static::once())->method('count')->willReturn(6);

        $func = Func::method('count');

        static::assertEquals(6, $func($mock));
    }

    /**
     * @since [*next-version*]
     */
    public function testMethodNoArgs()
    {
        $func = Func::method('count');

        $this->expectException(BadFunctionCallException::class);
        $func();
    }
}
