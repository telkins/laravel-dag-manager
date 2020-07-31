<?php

namespace Telkins\Dag\Tests;

use InvalidArgumentException;
use Telkins\Dag\Tests\Support\TestModel;
use Telkins\Dag\Tests\Support\CreatesEdges;

class IsDagManagedTraitTest extends TestCase
{
    use CreatesEdges;

    /**
     * Tests:  A
     *         |
     *         B  <-- get relations of this entry
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_get_all_relations_from_a_simple_chain()
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
         *  - we attempt to get DAG relations from B
         */
        $results = TestModel::dagRelationsOf($b->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from B -> A)
         *     - C (from C -> B)
         *     - D (from D -> C)
         */
        $this->assertCount(3, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($c->id, $results->shift()->id);
        $this->assertSame($d->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C  <-- get relations of entry "B"
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_all_relations_from_a_complex_box_diamond_part_i()
    {
        /**
         * Arrange/Given:
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

        /**
         * Act/When:
         *  - we attempt to get DAG relations from B
         */
        $results = TestModel::dagRelationsOf($b->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from B -> A)
         *     - D (from D -> B)
         *     - E (from E -> B)
         *     - F (from F -> D -> B *and/or* F -> E -> B)
         */
        $this->assertCount(4, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($d->id, $results->shift()->id);
        $this->assertSame($e->id, $results->shift()->id);
        $this->assertSame($f->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C  <-- get relations of entry "C"
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_get_all_relations_from_a_complex_box_diamond_part_ii()
    {
        /**
         * Arrange/Given:
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

        /**
         * Act/When:
         *  - we attempt to get DAG relations from C
         */
        $results = TestModel::dagRelationsOf($c->id, $this->source)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the following entries:
         *     - A (from C -> A)
         *     - E (from E -> C)
         *     - F (from F -> E)
         */
        $this->assertCount(3, $results);
        $this->assertSame($a->id, $results->shift()->id);
        $this->assertSame($e->id, $results->shift()->id);
        $this->assertSame($f->id, $results->shift()->id);
    }

    /**
     * Tests:  A
     *         |
     *         B  <-- get relations of this entry
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     * @dataProvider provideMaxHopsForSimpleChainAllRelations
     */
    public function it_can_get_all_relations_from_a_simple_chain_constrained_by_max_hops($maxHops, $expectedNames)
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
         *  - we attempt to get all DAG relations of B, constrained by $maxHops
         */
        $results = TestModel::dagRelationsOf($b->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue($results->pluck('name')->contains($expectedName));
        });
    }

    public function provideMaxHopsForSimpleChainAllRelations()
    {
        return [
            [0, ['a', 'c']],
            [1, ['a', 'c', 'd']],
            [2, ['a', 'c', 'd']],
            [3, ['a', 'c', 'd']],
            [null, ['a', 'c', 'd']],
            [-1, ['a', 'c']],
        ];
    }

    /**
     * Tests:  A
     *        / \
     *       B   C   <-- get relations of entry "B"
     *       | \ |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     * @dataProvider provideMaxHopsForComplexBoxDiamondAllRelations
     */
    public function it_can_get_all_relations_from_a_complex_box_diamond_constrained_by_max_hops($maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

        /**
         * Act/When:
         *  - we attempt to get all DAG relations from B, constrained by $maxHops
         */
        $results = TestModel::dagRelationsOf($b->id, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue($results->pluck('name')->contains($expectedName));
        });
    }

    public function provideMaxHopsForComplexBoxDiamondAllRelations()
    {
        return [
            [0, ['a', 'd', 'e']],
            [1, ['a', 'd', 'e', 'f']],
            [2, ['a', 'd', 'e', 'f']],
            [3, ['a', 'd', 'e', 'f']],
            [null, ['a', 'd', 'e', 'f']],
            [-1, ['a', 'd', 'e']],
        ];
    }

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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
            $this->assertTrue($results->pluck('name')->contains($expectedName));
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
            [-1, ['b']],
        ];
    }

    /**
     * Tests:  A   <-- get descendants of entry "A"
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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
            $this->assertTrue($results->pluck('name')->contains($expectedName));
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
            [-1, ['b', 'c']],
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
            $this->assertTrue($results->pluck('name')->contains($expectedName));
        });
    }

    public function provideMaxHopsForSimpleChainUp()
    {
        return [
            [0, ['c']],
            [1, ['c', 'b']],
            [2, ['c', 'b', 'a']],
            [null, ['c', 'b', 'a']],
            [-1, ['c']],
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
         *  - we have a "complex box diamond"
         */
        $this->buildComplexBoxDiamond();

        $a = TestModel::where('name', 'a')->first();
        $b = TestModel::where('name', 'b')->first();
        $c = TestModel::where('name', 'c')->first();
        $d = TestModel::where('name', 'd')->first();
        $e = TestModel::where('name', 'e')->first();
        $f = TestModel::where('name', 'f')->first();

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
            $this->assertTrue($results->pluck('name')->contains($expectedName));
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
            [-1, ['e', 'd']],
        ];
    }

    /**
     * @test
     * @dataProvider provideComplexInputForComplexBoxDiamondDown
     */
    public function it_can_get_descendants_with_complex_input($modelNames, $maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have a "complex box diamond"
         */
        $models = $this->buildComplexBoxDiamond();

        // An array of names => an array of IDs, a single name string => an integer ID...
        if (is_array($modelNames)) {
            $modelIds = collect($modelNames)->map(function ($name) use ($models) {
                return $models->where('name', $name)->first()->id;
            })->all();
        } else {
            $modelIds = $models->where('name', $modelNames)->first()->id;
        }

        /**
         * Act/When:
         *  - we attempt to get DAG descendants
         */
        $results = TestModel::dagDescendantsOf($modelIds, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue($results->pluck('name')->contains($expectedName));
        });
    }

    public function provideComplexInputForComplexBoxDiamondDown()
    {
        return [
            [['b', 'c'], null, ['d', 'e', 'f']],
            [['b', 'c'], 0, ['d', 'e']],
            [['b', 'c'], 5, ['d', 'e', 'f']],
            [['c', 'd'], null, ['e', 'f']],
            [['c', 'd'], 0, ['e', 'f']],
            [['c', 'd'], 5, ['e', 'f']],
            [['a', 'd'], null, ['b', 'c', 'd', 'e', 'f']],
            [['a', 'd'], 0, ['b', 'c', 'f']],
            [['a', 'd'], 5, ['b', 'c', 'd', 'e', 'f']],
            [['a', 'f'], null, ['b', 'c', 'd', 'e', 'f']],
            [['a', 'f'], 0, ['b', 'c']],
            [['a', 'f'], 5, ['b', 'c', 'd', 'e', 'f']],
            ['a', null, ['b', 'c', 'd', 'e', 'f']],
            ['a', 0, ['b', 'c']],
            ['a', 5, ['b', 'c', 'd', 'e', 'f']],
            ['f', null, []],
            ['f', 0, []],
            ['f', 5, []],
            [['a', 'b', 'c', 'd', 'e', 'f'], null, ['b', 'c', 'd', 'e', 'f']],
            [['a', 'b', 'c', 'd', 'e', 'f'], 0, ['b', 'c', 'd', 'e', 'f']],
            [['a', 'b', 'c', 'd', 'e', 'f'], 5, ['b', 'c', 'd', 'e', 'f']],
        ];
    }

    /**
     * @test
     * @dataProvider provideComplexInputForComplexBoxDiamondUp
     */
    public function it_can_get_ancestors_with_complex_input($modelNames, $maxHops, $expectedNames)
    {
        /**
         * Arrange/Given:
         *  - we have a "complex box diamond"
         */
        $models = $this->buildComplexBoxDiamond();

        // An array of names => an array of IDs, a single name string => an integer ID...
        if (is_array($modelNames)) {
            $modelIds = collect($modelNames)->map(function ($name) use ($models) {
                return $models->where('name', $name)->first()->id;
            })->all();
        } else {
            $modelIds = $models->where('name', $modelNames)->first()->id;
        }

        /**
         * Act/When:
         *  - we attempt to get DAG ancestors
         */
        $results = TestModel::dagAncestorsOf($modelIds, $this->source, $maxHops)->get();

        /**
         * Assert/Then:
         *  - we have a collection with the expected number of entries
         *  - each of the expected names can be found in the results
         */
        $this->assertCount(count($expectedNames), $results);
        collect($expectedNames)->each(function ($expectedName) use ($results) {
            $this->assertTrue($results->pluck('name')->contains($expectedName));
        });
    }

    public function provideComplexInputForComplexBoxDiamondUp()
    {
        return [
            [['d', 'e'], null, ['a', 'b', 'c']],
            [['d', 'e'], 0, ['b', 'c']],
            [['d', 'e'], 5, ['a', 'b', 'c']],
            [['c', 'd'], null, ['b', 'a']],
            [['c', 'd'], 0, ['b', 'a']],
            [['c', 'd'], 5, ['b', 'a']],
            [['b', 'f'], null, ['a', 'b', 'c', 'd', 'e']],
            [['b', 'f'], 0, ['a', 'd', 'e']],
            [['b', 'f'], 5, ['a', 'b', 'c', 'd', 'e']],
            [['a', 'f'], null, ['a', 'b', 'c', 'd', 'e']],
            [['a', 'f'], 0, ['d', 'e']],
            [['a', 'f'], 5, ['a', 'b', 'c', 'd', 'e']],
            ['a', null, []],
            ['a', 0, []],
            ['a', 5, []],
            ['f', null, ['a', 'b', 'c', 'd', 'e']],
            ['f', 0, ['d', 'e']],
            ['f', 5, ['a', 'b', 'c', 'd', 'e']],
            [['a', 'b', 'c', 'd', 'e', 'f'], null, ['a', 'b', 'c', 'd', 'e']],
            [['a', 'b', 'c', 'd', 'e', 'f'], 0, ['a', 'b', 'c', 'd', 'e']],
            [['a', 'b', 'c', 'd', 'e', 'f'], 5, ['a', 'b', 'c', 'd', 'e']],
        ];
    }

    /**
     * @test
     * @dataProvider providInvalidModelIdArguments
     */
    public function it_rejects_invalid_model_id_arguments_to_dag_descendants_of_scope($invalidArgument)
    {
        /**
         * Arrange/Given:
         *  - ...
         */
        // ...

        /**
         * Assert/Then:
         *  - the expected exception will be thrown
         */
        $this->expectException(InvalidArgumentException::class);

        /**
         * Act/When:
         *  - attempt to insert an edge that would create a circular loop
         */
        TestModel::dagDescendantsOf($invalidArgument, $this->source);
    }

    /**
     * @test
     * @dataProvider providInvalidModelIdArguments
     */
    public function it_rejects_invalid_model_id_arguments_to_dag_ancestors_of_scope($invalidArgument)
    {
        /**
         * Arrange/Given:
         *  - ...
         */
        // ...

        /**
         * Assert/Then:
         *  - the expected exception will be thrown
         */
        $this->expectException(InvalidArgumentException::class);

        /**
         * Act/When:
         *  - attempt to insert an edge that would create a circular loop
         */
        TestModel::dagAncestorsOf($invalidArgument, $this->source);
    }

    /**
     * @test
     * @dataProvider providInvalidModelIdArguments
     */
    public function it_rejects_invalid_model_id_arguments_to_dag_relations_of_scope($invalidArgument)
    {
        /**
         * Arrange/Given:
         *  - ...
         */
        // ...

        /**
         * Assert/Then:
         *  - the expected exception will be thrown
         */
        $this->expectException(InvalidArgumentException::class);

        /**
         * Act/When:
         *  - attempt to insert an edge that would create a circular loop
         */
        TestModel::dagRelationsOf($invalidArgument, $this->source);
    }

    public function providInvalidModelIdArguments()
    {
        return [
            [null],
            [true],
            [false],
            [new \stdClass()],
            ['a.string'],
            [collect([1, 2, 3])], /** @todo if/when collections are accepted, this will need to be removed. */
            [1.0],
        ];
    }

    /**
     * Builds:  A
     *         / \
     *        B   C
     *        | \ |
     *        D   E
     *         \ /
     *          F
     */
    protected function buildComplexBoxDiamond()
    {
        /**
         * We have the following test models:
         *  - a - f
         */
        $models = collect([
            $a = TestModel::create(['name' => 'a']),
            $b = TestModel::create(['name' => 'b']),
            $c = TestModel::create(['name' => 'c']),
            $d = TestModel::create(['name' => 'd']),
            $e = TestModel::create(['name' => 'e']),
            $f = TestModel::create(['name' => 'f']),
        ]);

        /**
         * We have the following dag edge(s) in place:
         *  - B -> A
         *  - D -> B
         *  - E -> B
         *  - C -> A
         *  - E -> C
         *  - F -> D
         *  - F -> E
         */
        $this->createEdge($b->id, $a->id);
        $this->createEdge($d->id, $b->id);
        $this->createEdge($e->id, $b->id);
        $this->createEdge($c->id, $a->id);
        $this->createEdge($e->id, $c->id);
        $this->createEdge($f->id, $d->id);
        $this->createEdge($f->id, $e->id);

        return $models;
    }
}
