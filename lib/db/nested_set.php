<?php

namespace {

	/**
	 * Recalculates the left and right edge values for a tree table.
	 *
	 * This function operates on a table such as

	 		CREATE TABLE tree(
				id				INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				parent_id	INT NOT NULL REFERENCES tree(id),
				l					INT,
				r					INT
			)
	 *
	 * to transform the tree into a nested set.
	 *
	 * The implementation uses client side code to load the tree into memory and recursively process it.
	 * The down side is that the tree must fit into memory, but the upside is that it is much faster
	 * than using a stored procedure.
	 *
	 * The default implementation executes a single prepared UPDATE statement for each node,
	 * so the caller might want to wrap this method inside a database transaction.
	 *
	 * @param PDODB $db
	 * @param string $treetable	The table name
	 * @param string $idcol	The name of the primary key column
	 * @param string $parentcol The name of the column holding the parent reference
	 * @param string $lcol The column name holding the left edge
	 * @param string $rcol The column name holding the right edge
	 */
	function tree_to_nested_set( $db, $treetable, $idcol = 'id', $parentcol = 'parent_id', $lcol = 'l', $rcol = 'r' ) {

		list( $root, $map ) = WT\DB\NestedSet\process_tree( $db, $treetable, $idcol, $parentcol, $lcol, $rcol );

		WT\DB\NestedSet\update1( $db, $treetable, $idcol, $lcol, $rcol, $map );
	}

}

namespace WT\DB\NestedSet {

	use \Check;

	/**
	 * Fetches the parent-child relationships and builds a tree with nested set edges.
	 */
	function process_tree( $db, $treetable, $idcol, $parentcol, $lcol, $rcol )
	{
		Check::identifier( $treetable );
		Check::identifier( $idcol );
		Check::identifier( $lcol );
		Check::identifier( $rcol );

		$rows = $db->q( "SELECT $idcol, $parentcol FROM $treetable" )->fetchAll( \PDO::FETCH_ASSOC );

		$map = make_object_array( array_hash( $rows, $idcol ) );
		$children = array_hash( $rows, $parentcol, [ $idcol ] );
		$root = gentree( $children, $map, null );

		return [ $root, $map ];
	}

	/**
	 * Recursively processes node trees, calculating the left and right edges.
	 */
	function gentree( & $children, & $map, $k = null, & $edge = -1 ) {

		$el = isset( $map[ $k ] ) ? $map[ $k ] : (object)['root'=>'root'];
		$el->edge_l = ++$edge;

		if ( !isset( $children[ $k ] ) ) {
			$el->edge_r = ++$edge;
			return $el;
		}
	#	else
	#		$cur = $children[ $k ];

	#	foreach ( $cur as $i )
		foreach ( $children[ $k ] as $i )
			$el->children[ $i ] = gentree( $children, $map, $i, $edge );
		
		$el->edge_r = ++$edge;
		
		return $el;
	}

	/**
	 * Performs an update of the left/right edge columns of the tree table using one UPDATE query per node.
	 * Wrap in a database transaction for excellent performance.
	 */
	function update1( $db, $treetable, $idcol, $lcol, $rcol, $map )
	{
		foreach ( $map as $i => $o )
			if ( $o->id )
				if ( ! isset( $o->edge_l ) )
					warn( "Missing edge on " . print_r( $o, 1 ) );
				else
				executeUpdateQuery( $db, $treetable, [ $idcol => $o->id ],
					[ $lcol => $o->edge_l, $rcol => $o->edge_r ]
				);
	}

	/**
	 * This updates the edges of the tree table using a temporary table and only 3 queries: a CREATE, INSERT and an UPDATE.
	 * It makes use of a potentially large INSERT query.
	 */
	function update2( $db, $treetable, $idcol, $lcol, $rcol, $map )
	{
		$tmptable = Check::identifier( "tmp_lr_$treetable" );
		$db->q( "CREATE TEMPORARY TABLE $tmptable( id INT NOT NULL, l INT NOT NULL, r INT NOT NULL )" );
		$vals = "";
		foreach ( $map as $i => $o )
			if ( $o->id )
				$vals .= ", ( $o->id, $o->edge_l, $o->edge_r )";
		$db->q( "INSERT INTO $tmptable VALUES " . substr( $vals, 1 ) );
		$db->q( "UPDATE $treetable t LEFT JOIN $tmptable x USING($idcol) SET t.$lcol=x.l, t.$rcol=x.r" );
	}

	/**
	 * Updates the edges of the tree table using a single query.
	 * It makes use of an ad-hoc inline table using UNION.
	 * This is the fastest method. Wrapping it in a transaction makes no difference.
	 */
	function update3( $db, $treetable, $idcol, $lcol, $rcol, $map )
	{
		$vals = "";
		foreach ( $map as $i => $o )
			if ( $o->id )
				if ( ! $vals )
					$vals .= "SELECT $o->id $idcol, $o->edge_l l, $o->edge_r r";
				else
					$vals .= " UNION SELECT $o->id, $o->edge_l, $o->edge_r";
		$db->q( $q ="UPDATE $treetable t LEFT JOIN ( $vals ) x USING($idcol) SET t.$lcol=x.l, t.$rcol=x.r" );
	}
}
