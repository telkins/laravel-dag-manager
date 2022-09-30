<?php

declare(strict_types=1);

return [

    /**
     *-------------------------------------------------------------------------
     * Max Hops
     *-------------------------------------------------------------------------
     *
     * This value represents the maximum number of hops that are allowed where
     * hops "[i]ndicates how many vertex hops are necessary for the path; it is
     * zero for direct edges".
     *
     * The more hops that are allowed (and used), then the more DAG edges will
     * be created.  This will have an increasing impact on performance, space,
     * and memory.  Whether or not it's negligible, noticeable, or impactful
     * depends on a variety of factors.
     */

    'max_hops' => 5,

    /**
     *-------------------------------------------------------------------------
     * Default Database Connection Name
     *-------------------------------------------------------------------------
     *
     * This is the name of the database connection where the dag table can be
     * found.
     *
     * Set to `null` to use the default connection.
     */

    'default_database_connection_name' => null,

    /**
     *-------------------------------------------------------------------------
     * Table Name
     *-------------------------------------------------------------------------
     *
     * This is the name of the table where the dag structure will be stored.
     */

    'table_name' => 'dag_edges',

    /**
     *-------------------------------------------------------------------------
     * Edge Model
     *-------------------------------------------------------------------------
     *
     * The fully qualified class name of the DAG edge model.
     */

    'edge_model' => \Telkins\Dag\Models\DagEdge::class,

];
