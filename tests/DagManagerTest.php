<?php

namespace Telkins\Dag\Tests;

use Telkins\Dag\Models\DagEdge;
use Illuminate\Support\Collection;
use Telkins\Dag\Exceptions\TooManyHopsException;
use Telkins\Dag\Exceptions\CircularReferenceException;

class DagManagerTest extends TestCase
{
    protected $a = 1;
    protected $b = 2;
    protected $c = 3;
    protected $d = 4;
    protected $e = 5;
    protected $f = 6;

    protected $source = 'test-source';

    protected function createEdge(int $startVertex, int $endVertex, string $source = null)
    {
        return dag()->createEdge($startVertex, $endVertex, ($source ?? $this->source));
    }

    protected function assertExpectedEdge(DagEdge $actual, int $startVertex, int $endVertex, int $hops, string $source = null)
    {
        $this->assertSame($startVertex, $actual->start_vertex);
        $this->assertSame($endVertex, $actual->end_vertex);
        $this->assertSame($hops, $actual->hops);
        $this->assertSame(($source ?? $this->source), $actual->source);
    }

    /**
     * Tests:  A  Inserting A-B
     *         |
     *         B
     *
     * @test
     */
    public function it_can_add_a_simple_edge()
    {
        /**
         * Arrange/Given:
         *  - ...
         */
        // ...

        /**
         * Act/When:
         *  - we create an edge
         */
        $newEdges = $this->createEdge($this->b, $this->a);

        /**
         * Assert/Then:
         *  - we received a collection with one new edge
         *  - the new edge's attributes are as expected
         *  - the new edge matches what we find in the database
         *  - only one edge was created
         */
        $this->assertInstanceOf(Collection::class, $newEdges);
        $this->assertCount(1, $newEdges);
        tap($newEdges->first(), function ($edge) {
            $this->assertEquals($edge, DagEdge::first());
            $this->assertExpectedEdge($edge, $this->b, $this->a, 0);
        });
        $this->assertCount(1, DagEdge::all());
    }

    /**
     * One cannot insert an edge that would create a circular loop.
     *
     * Tests:  A  Inserting A-B, B-C...followed by C-A
     *         |
     *         B
     *         |
     *         C
     *         |  <-- this should fail
     *         A
     *
     * @test
     */
    public function it_does_not_allow_circular_references()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         */

        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->c, $this->b);

        /**
         * Assert/Then:
         *  - the expected exception will be thrown
         */
        $this->expectException(CircularReferenceException::class);

        /**
         * Act/When:
         *  - attempt to insert an edge that would create a circular loop
         */
        $this->createEdge($this->a, $this->c);
    }

    /**
     * Tests:  A  Inserting A-B...twice
     *         |
     *         B
     *
     *         A
     *         |
     *         B
     *
     * @test
     */
    public function it_returns_null_when_trying_to_add_a_duplicate()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         */
        $this->createEdge($this->b, $this->a);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - B -> A
         */
        $newEdges = $this->createEdge($this->b, $this->a);

        /**
         * Assert/Then:
         *  - results of last insert are as expected
         */
        $this->assertNull($newEdges);
    }

    /**
     * Tests:  A  Inserting A-B, B-C, C-D
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_build_a_simple_chain()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->c, $this->b);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - D -> C
         */
        $newEdges = $this->createEdge($this->d, $this->c);

        /**
         * Assert/Then:
         *  - results of last insert are as expected
         *  - we have a collection with the following new entries:
         *     - D -> C, 0 hops
         *     - D -> B, 1 hops
         *     - D -> A, 2 hops
         */
        $this->assertCount(3, $newEdges);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->c, 0);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->b, 1);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->a, 2);
    }

    /**
     * Tests:  A  Inserting C-D, B-C, A-B
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_build_a_simple_chain_in_reverse()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - D -> C
         *     - C -> B
         */
        $this->createEdge($this->d, $this->c);
        $this->createEdge($this->c, $this->b);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - B -> A
         */
        $newEdges = $this->createEdge($this->b, $this->a);

        /**
         * Assert/Then:
         *  - results of last insert are as expected
         *  - we have a collection with the following new entries:
         *     - B -> A, 0 hops
         *     - C -> A, 1 hops
         *     - D -> A, 2 hops
         */
        $this->assertCount(3, $newEdges);
        $this->assertExpectedEdge($newEdges->shift(), $this->b, $this->a, 0);
        $this->assertExpectedEdge($newEdges->shift(), $this->c, $this->a, 1);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->a, 2);
    }

    /**
     * Tests:  A  Inserting A-B, C-D, B-C
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_build_a_simple_chain_in_inside_out()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> C
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->d, $this->c);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - C -> B
         */
        $newEdges = $this->createEdge($this->c, $this->b);

        /**
         * Assert/Then:
         *  - results of last insert are as expected
         *  - we have a collection with the following new entries:
         *     - C -> B, 0 hops
         *     - D -> B, 1 hops
         *     - C -> A, 1 hops
         *     - D -> A, 2 hops
         */
        $this->assertCount(4, $newEdges);
        $this->assertExpectedEdge($newEdges->shift(), $this->c, $this->b, 0);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->b, 1);
        $this->assertExpectedEdge($newEdges->shift(), $this->c, $this->a, 1);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->a, 2);
    }

    /**
     * Tests:  A  Inserting B-C, C-D, A-B
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_build_a_simple_chain_in_inside_out_and_reverse()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - C -> B
         *     - D -> C
         */
        $this->createEdge($this->c, $this->b);
        $this->createEdge($this->d, $this->c);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - B -> A
         */
        $newEdges = $this->createEdge($this->b, $this->a);

        /**
         * Assert/Then:
         *  - results of last insert are as expected
         *  - we have a collection with the following new entries:
         *     - B -> A, 0 hops
         *     - C -> A, 1 hops
         *     - D -> A, 2 hops
         */
        $this->assertCount(3, $newEdges);
        $this->assertExpectedEdge($newEdges->shift(), $this->b, $this->a, 0);
        $this->assertExpectedEdge($newEdges->shift(), $this->c, $this->a, 1);
        $this->assertExpectedEdge($newEdges->shift(), $this->d, $this->a, 2);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C
     *        \ /
     *         D
     *
     * @test
     */
    public function it_can_build_a_simple_diamond()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> A
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->c, $this->a);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - D -> B
         *     - D -> C
         */
        $newEdges1 = $this->createEdge($this->d, $this->b);
        $newEdges2 = $this->createEdge($this->d, $this->c);

        /**
         * Assert/Then:
         *  - results of last inserts are as expected
         *  - we have a collection with the following new entries:
         *     - D -> B, 0 hops
         *     - D -> A, 1 hops
         *  - we have a collection with the following new entries:
         *     - D -> C, 0 hops
         *     - D -> A, 1 hops
         */
        $this->assertCount(2, $newEdges1);
        $this->assertExpectedEdge($newEdges1->shift(), $this->d, $this->b, 0);
        $this->assertExpectedEdge($newEdges1->shift(), $this->d, $this->a, 1);

        $this->assertCount(2, $newEdges2);
        $this->assertExpectedEdge($newEdges2->shift(), $this->d, $this->c, 0);
        $this->assertExpectedEdge($newEdges2->shift(), $this->d, $this->a, 1);
    }

    /**
     * Tests:  A
     *        / \
     *       B   C
     *       |   |
     *       D   E
     *        \ /
     *         F
     *
     * @test
     */
    public function it_can_build_a_simple_box_diamond()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - D -> B
         *     - C -> A
         *     - E -> C
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->d, $this->b);
        $this->createEdge($this->c, $this->a);
        $this->createEdge($this->e, $this->c);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - F -> D
         *     - F -> E
         */
        $newEdges1 = $this->createEdge($this->f, $this->d);
        $newEdges2 = $this->createEdge($this->f, $this->e);

        /**
         * Assert/Then:
         *  - results of last inserts are as expected
         *  - we have a collection with the following new entries:
         *     - F -> D, 0 hops
         *     - F -> B, 1 hops
         *     - F -> A, 2 hops
         *  - we have a collection with the following new entries:
         *     - F -> E, 0 hops
         *     - F -> C, 1 hops
         *     - F -> A, 2 hops
         */
        $this->assertCount(3, $newEdges1);
        $this->assertExpectedEdge($newEdges1->shift(), $this->f, $this->d, 0);
        $this->assertExpectedEdge($newEdges1->shift(), $this->f, $this->b, 1);
        $this->assertExpectedEdge($newEdges1->shift(), $this->f, $this->a, 2);

        $this->assertCount(3, $newEdges2);
        $this->assertExpectedEdge($newEdges2->shift(), $this->f, $this->e, 0);
        $this->assertExpectedEdge($newEdges2->shift(), $this->f, $this->c, 1);
        $this->assertExpectedEdge($newEdges2->shift(), $this->f, $this->a, 2);
    }

    /**
     * Tests:  A
     *        /|\
     *       B C D
     *        \|/
     *         E
     *
     * @test
     */
    public function it_can_build_a_simple_tri_diamond()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> A
         *     - D -> A
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->c, $this->a);
        $this->createEdge($this->d, $this->a);

        /**
         * Act/When:
         *  - insert the following dag edge(s):
         *     - E -> B
         *     - E -> C
         *     - E -> D
         */
        $newEdges1 = $this->createEdge($this->e, $this->b);
        $newEdges2 = $this->createEdge($this->e, $this->c);
        $newEdges3 = $this->createEdge($this->e, $this->d);

        /**
         * Assert/Then:
         *  - results of last inserts are as expected
         *  - we have a collection with the following new entries:
         *     - E -> B, 0 hops
         *     - E -> A, 1 hops
         *  - we have a collection with the following new entries:
         *     - E -> C, 0 hops
         *     - E -> A, 1 hops
         *  - we have a collection with the following new entries:
         *     - E -> D, 0 hops
         *     - E -> A, 1 hops
         */
        $this->assertCount(2, $newEdges1);
        $this->assertExpectedEdge($newEdges1->shift(), $this->e, $this->b, 0);
        $this->assertExpectedEdge($newEdges1->shift(), $this->e, $this->a, 1);

        $this->assertCount(2, $newEdges2);
        $this->assertExpectedEdge($newEdges2->shift(), $this->e, $this->c, 0);
        $this->assertExpectedEdge($newEdges2->shift(), $this->e, $this->a, 1);

        $this->assertCount(2, $newEdges3);
        $this->assertExpectedEdge($newEdges3->shift(), $this->e, $this->d, 0);
        $this->assertExpectedEdge($newEdges3->shift(), $this->e, $this->a, 1);
    }

    /**
     * @test
     * @dataProvider provideTooManyHops
     */
    public function it_cannot_exceed_max_hops($initialEdges, $lastStrawEdge)
    {
        /**
         * Arrange/Given:
         *  - prepare initial edges
         */
        foreach ($initialEdges as $edgeSet) {
            $this->createEdge($edgeSet['start_vertex'], $edgeSet['end_vertex']);
        }

        /**
         * Assert/Then:
         *  - the expected exception will be thrown
         */
        $this->expectException(TooManyHopsException::class);

        /**
         * Act/When:
         *  - insert the "last straw" edge
         */
        $this->createEdge($lastStrawEdge['start_vertex'], $lastStrawEdge['end_vertex']);
    }

    public function provideTooManyHops()
    {
        return [
            [   /**
                 * Tests:
                 *  - Initial:  1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7 (5 hops)
                 *  - Next: 7 -> 8 (6 hops)
                 */
                [ // Initial edges...
                    ['start_vertex' => 2, 'end_vertex' => 1],
                    ['start_vertex' => 3, 'end_vertex' => 2],
                    ['start_vertex' => 4, 'end_vertex' => 3],
                    ['start_vertex' => 5, 'end_vertex' => 4],
                    ['start_vertex' => 6, 'end_vertex' => 5],
                    ['start_vertex' => 7, 'end_vertex' => 6],
                ],
                [ // Last straw edge...
                    'start_vertex' => 8, 'end_vertex' => 7,
                ],
            ],
            [   /**
                 * Tests:
                 *  - Initial:  1 -> 2 -> 3 (1 hop)
                 *              4 -> 5 -> 6 -> 7 -> 8 (3 hops)
                 *  - Next: 3 -> 4 (6 hops)
                 */
                [ // Initial edges...
                    ['start_vertex' => 2, 'end_vertex' => 1],
                    ['start_vertex' => 3, 'end_vertex' => 2],
                    ['start_vertex' => 5, 'end_vertex' => 4],
                    ['start_vertex' => 6, 'end_vertex' => 5],
                    ['start_vertex' => 7, 'end_vertex' => 6],
                    ['start_vertex' => 8, 'end_vertex' => 7],
                ],
                [ // Last straw edge...
                    'start_vertex' => 4, 'end_vertex' => 3,
                ],
            ],
            [   /**
                 * Tests:
                 *  - Initial:  1 -> 2 -> 3 -> 4 (2 hops)
                 *              5 -> 6 -> 7 -> 8 (2 hops)
                 *  - Next: 4 -> 5 (6 hops)
                 */
                [ // Initial edges...
                    ['start_vertex' => 2, 'end_vertex' => 1],
                    ['start_vertex' => 3, 'end_vertex' => 2],
                    ['start_vertex' => 4, 'end_vertex' => 3],
                    ['start_vertex' => 6, 'end_vertex' => 5],
                    ['start_vertex' => 7, 'end_vertex' => 6],
                    ['start_vertex' => 8, 'end_vertex' => 7],
                ],
                [ // Last straw edge...
                    'start_vertex' => 5, 'end_vertex' => 4,
                ],
            ],
            [   /**
                 * Tests:
                 *  - Initial:  1 -> 2 -> 3 -> 4 -> 5 (3 hops)
                 *              6 -> 7 -> 8 (1 hop)
                 *  - Next: 5 -> 6 (6 hops)
                 */
                [ // Initial edges...
                    ['start_vertex' => 2, 'end_vertex' => 1],
                    ['start_vertex' => 3, 'end_vertex' => 2],
                    ['start_vertex' => 4, 'end_vertex' => 3],
                    ['start_vertex' => 5, 'end_vertex' => 4],
                    ['start_vertex' => 7, 'end_vertex' => 6],
                    ['start_vertex' => 8, 'end_vertex' => 7],
                ],
                [ // Last straw edge...
                    'start_vertex' => 6, 'end_vertex' => 5,
                ],
            ],
            [   /**
                 * Tests:
                 *  - Initial:  2 -> 3 -> 4 -> 5 -> 6 -> 7 -> 8 (5 hops)
                 *  - Next: 7 -> 8 (6 hops)
                 */
                [ // Initial edges...
                    ['start_vertex' => 3, 'end_vertex' => 2],
                    ['start_vertex' => 4, 'end_vertex' => 3],
                    ['start_vertex' => 5, 'end_vertex' => 4],
                    ['start_vertex' => 6, 'end_vertex' => 5],
                    ['start_vertex' => 7, 'end_vertex' => 6],
                    ['start_vertex' => 8, 'end_vertex' => 7],
                ],
                [ // Last straw edge...
                    'start_vertex' => 2, 'end_vertex' => 1,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function deleting_non_existant_edge_by_id_returns_false()
    {
        /**
         * Arrange/Given:
         *  - we have no dag edges defined
         */
        // ...

        /**
         * Act/When:
         *  - we attempt to delete the edge
         */
        $result = dag()->deleteEdge($this->b, $this->a, $this->source);

        /**
         * Assert/Then:
         *  - we receive the expected result
         */
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_delete_an_edge()
    {
        /**
         * Arrange/Given:
         *  - we have one dag edge defined
         */
        $edge = $this->createEdge($this->b, $this->a);

        /**
         * Act/When:
         *  - we attempt to delete the edge
         */
        $result = dag()->deleteEdge($this->b, $this->a, $this->source);

        /**
         * Assert/Then:
         *  - we receive the expected result
         *  - edge no longer found
         */
        $this->assertTrue($result);
        $this->assertCount(0, DagEdge::all());
    }

    /**
     * Tests:  A  Inserting A-B, B-C, C-D
     *         |
     *         B
     *         |
     *         C
     *         |
     *         D
     *
     * @test
     */
    public function it_can_delete_an_edge_from_a_simple_chain()
    {
        /**
         * Arrange/Given:
         *  - we have the following dag edge(s) in place:
         *     - B -> A
         *     - C -> B
         *     - D -> C
         */
        $this->createEdge($this->b, $this->a);
        $this->createEdge($this->c, $this->b);
        $this->createEdge($this->d, $this->c);

        /**
         * Act/When:
         *  - delete the following dag edge(s):
         *     - C -> B
         */
        $result = dag()->deleteEdge($this->c, $this->b, $this->source);

        /**
         * Assert/Then:
         *  - we receive the expected result
         *  - edge no longer found
         */
        $this->assertTrue($result);
        tap(DagEdge::all(), function ($edges) {
            $this->assertCount(2, $edges);
            $this->assertExpectedEdge($edges->shift(), $this->b, $this->a, 0);
            $this->assertExpectedEdge($edges->shift(), $this->d, $this->c, 0);
        });
    }
}
