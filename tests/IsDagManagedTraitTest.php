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
}
