<?php
namespace template;

class HTMLView extends \template\View {
	private $data = array();
	private static $_view_id = 0;

	public function __construct( &$state, $id, $label, $html ) {
		if ( ! $id ) $id = __CLASS__ . "_id_" . ++ self::$_view_id;
		parent::__construct( $state, $id, $label );
		$this->data = $html;
	}

	protected function _render() {
        $tmp = $this->data;
		if ( is_callable( $tmp ) )
        /*
            gettype($this->data)=='function'
		|| ( gettype($this->data)=='object' && get_class( $this->data ) == 'Closure' )
		|| ( gettype( $this->data ) == 'string' && function_exists( $this->data ) )
		*/
        {  $tmp(); }
		else echo $this->data;
	}
}


