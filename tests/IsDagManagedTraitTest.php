<?php

namespace Telkins\Dag\Tests;

use Telkins\Dag\Tests\Support\TestModel;
use Telkins\Dag\Tests\Support\CreatesEdges;

class IsDagManagedTraitTest extends TestCase
{
    use CreatesEdges;

    /**
     * Tests:  A
     *         |
     *         B  <-- get descendants of this entry
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_get_descendants_from_a_simple_chain()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - d
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         *     - D -> C
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($c->id, $b->id);
        $this->createEdge($d->id, $c->id);

        /**
         * Act/When:
         *  - we attempt to get DAG descendants from B
         */
        $results = TestModel::dagDescendantsOf($b->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - C (from C -> B)
         *     - D (from D -> C)
         */
        $this->assertCount(2, $results);
        $this->assertSame($c->id, $results->shift()->id);
        $this->assertSame($d->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C  <-- get descendants of entry "B"
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_descendants_from_a_complex_box_diamond_part_i()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG descendants from B
         */
        $results = TestModel::dagDescendantsOf($b->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - D (from D -> B)
         *     - E (from E -> B)
         *     - F (from F -> D -> B *and/or* F -> E -> B)
         */
        $this->assertCount(3, $results);
        $this->assertSame($d->id, $results->shift()->id);
        $this->assertSame($e->id, $results->shift()->id);
        $this->assertSame($f->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C  <-- get descendants of entry "C"
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_descendants_from_a_complex_box_diamond_part_ii()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG descendants from C
         */
        $results = TestModel::dagDescendantsOf($c->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - E (from E -> C)
         *     - F (from F -> E)
         */
        $this->assertCount(2, $results);
        $this->assertSame($e->id, $results->shift()->id);
        $this->assertSame($f->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *         |
     *         B
     *         |
     *         C  <-- get ancestors of this entry
     *         |
     *         D
     *
     * @test
     */
    public function it_can_get_ancestors_from_a_simple_chain()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - d
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         *     - D -> C
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($c->id, $b->id);
        $this->createEdge($d->id, $c->id);

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors of C
         */
        $results = TestModel::dagAncestorsOf($c->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from B -> A)
         *     - B (from C -> B)
         */
        $this->assertCount(2, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($b->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C
     *       | \ |
     *       D   E  <-- get ancestors of entry "E"
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_ancestors_from_a_complex_box_diamond_part_i()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors of E
         */
        $results = TestModel::dagAncestorsOf($e->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from E -> B -> A *and/or* E -> C -> A)
         *     - B (from E -> B)
         *     - C (from E -> C)
         */
        $this->assertCount(3, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($b->id, $results->shift()->id);
        $this->assertSame($c->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C
     *       | \ |
     *       D   E  <-- get ancestors of entry "D"
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_ancestors_from_a_complex_box_diamond_part_ii()
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors of D
         */
        $results = TestModel::dagAncestorsOf($d->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from D -> B -> A)
         *     - B (from D -> B)
         */
        $this->assertCount(2, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($b->id, $results->shift()->id);
    }

    /**
     * Tests:  A  <-- get descendants of this entry
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     * @dataProvider provideMaxHopsForSimpleChain
     */
    public function it_can_get_descendants_from_a_simple_chain_constrained_by_max_hops($maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - d
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         *     - D -> C
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($c->id, $b->id);
        $this->createEdge($d->id, $c->id);

        /**
         * Act/When:
         *  - we attempt to get DAG descendants of A, constrained by $maxHops
         */
        $results = TestModel::dagDescendantsOf($a->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue(in_array($expectedName, $results->pluck('name')->all()));
        });
    }

    public function provideMaxHopsForSimpleChain()
    {
        return [
            [0, ['b']],
            [1, ['b', 'c']],
            [2, ['b', 'c', 'd']],
            [3, ['b', 'c', 'd']],
            [null, ['b', 'c', 'd']],
        ];
    }

    /**
     * Tests:  A   <-- get descendants of entry "B"
     *        / \
     *       B   C
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     * @dataProvider provideMaxHopsForComplexBoxDiamond
     */
    public function it_can_get_descendants_from_a_complex_box_diamond_constrained_by_max_hops($maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG descendants from A, constrained by $maxHops
         */
        $results = TestModel::dagDescendantsOf($a->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue(in_array($expectedName, $results->pluck('name')->all()));
        });
    }

    public function provideMaxHopsForComplexBoxDiamond()
    {
        return [
            [0, ['b', 'c']],
            [1, ['b', 'c', 'd', 'e']],
            [2, ['b', 'c', 'd', 'e', 'f']],
            [3, ['b', 'c', 'd', 'e', 'f']],
            [null, ['b', 'c', 'd', 'e', 'f']],
        ];
    }

    /**
     * Tests:  A
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D  <-- get ancestors of this entry
     *
     * @test
     * @dataProvider provideMaxHopsForSimpleChainUp
     */
    public function it_can_get_ancestors_from_a_simple_chain_constrained_by_max_hops($maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - d
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         *     - D -> C
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($c->id, $b->id);
        $this->createEdge($d->id, $c->id);

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors of D, constrained by $maxHops
         */
        $results = TestModel::dagAncestorsOf($d->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue(in_array($expectedName, $results->pluck('name')->all()));
        });
    }

    public function provideMaxHopsForSimpleChainUp()
    {
        return [
            [0, ['c']],
            [1, ['c', 'b']],
            [2, ['c', 'b', 'a']],
            [null, ['c', 'b', 'a']],
        ];
    }

    /**
     * Tests:  A
     *        / \
     *       B   C
     *       | \ |
     *       D   E
     *        \ /
     *         F   <-- get ancestors of entry "F"
     *
     * @test
     * @dataProvider provideMaxHopsForComplexBoxDiamondUp
     */
    public function it_can_get_ancestors_from_a_complex_box_diamond_constrained_by_max_hops($maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have the following test models:
         *     - a - f
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - E -> B
         *     - C -> A
         *     - E -> C
         *     - F -> D
         *     - F -> E
         */
        $a = TestModel::create(['name' => 'a']);
        $b = TestModel::create(['name' => 'b']);
        $c = TestModel::create(['name' => 'c']);
        $d = TestModel::create(['name' => 'd']);
        $e = TestModel::create(['name' => 'e']);
        $f = TestModel::create(['name' => 'f']);
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors of F, constrained by $maxHops
         */
        $results = TestModel::dagAncestorsOf($f->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue(in_array($expectedName, $results->pluck('name')->all()));
        });
    }

    public function provideMaxHopsForComplexBoxDiamondUp()
    {
        return [
            [0, ['e', 'd']],
            [1, ['e', 'd', 'b', 'c']],
            [2, ['e', 'd', 'b', 'c', 'a']],
            [3, ['e', 'd', 'b', 'c', 'a']],
            [null, ['e', 'd', 'b', 'c', 'a']],
        ];
    }
}
