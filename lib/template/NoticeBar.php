<?php
namespace template;

class NoticeBar
{
	var $template;
	var $rendered = false;

	public function __construct( $template )
	{
		$this->template = $template;
	}

	public $notifications = array();
	public $messages = array();
	public $alerts = array();

	public function alert( $msg ) {
		//static $__DEBUG_noticebar = 0; echo "<pre>NOTICEBAR ALERT ".($__DEBUG_noticebar++)."</pre>";
		//ob_start();
		//debug_print_backtrace();$msg.="<pre>".htmlentities(ob_get_clean())."</pre>";
		//foreach ( debug_backtrace() as $v )
		//	echo $v['file'].":".$v['line'].': '.$v['function']."\n";
		//$msg.="<pre>".htmlentities(ob_get_clean())."</pre>";


		if ( $this->rendered )
		{
			warn( $msg ); // TODO: javascript update noticebar
		}
		else 
			$this->alerts[] = array_merge( array(
					'title' => 'WARNING',
					'body'  => $msg,
					'link' => '',
					'hash' => hash('md5', $msg )
			),
			is_array( $msg ) ? $msg : array()
		);
	}

	public function message( $title, $body ) {
		$this->messages[] = array(
					'link' => '#',
					'img'=>'http://www.gravatar.com/avatar/4748717147651ff692262a57db31c5ec?s=36',
					'title'=> $title,
					'body' => $body,
					'time'=>'vanochtend',
					'hash' => hash('md5', $msg )
				);
	}

	public function notify( $title, $body ) {
	}

	/** AJAX  see api/noticebar and javascript below */
	function filter_notifications() { return $this->_filter( $this->notifications, 'notifications' ); }
	function filter_messages() { return $this->_filter( $this->messages, 'messages' ); }
	function filter_alerts() { return $this->_filter( $this->alerts, 'alerts' ); }

	private function _filter( $list, $what )
	{
		// weed out seen alerts
		array_filter( $list,
			function(&$v) use($what) { return ! ($v['seen'] = in_array( $v['hash'], gd( $_SESSION[__CLASS__]["seen-$what"], array() ) ) );}
		);
		// return list of unseen alerts
		return array_map( function($v){ return $v['hash'];}, $list );
	}

	public function render()
	{
		$this->rendered = true;

		//$this->message('');

		global $request;

		// XXX this was once in menu:
		// list( $extra_menu, $extra_menu_class) = $this->manage_theme();

		if ( isset( $extra_menu ) ) // XXX HACK
		{
			if ( !isset( $extra_menu_class ) )
				$extra_menu_class='';
			echo "<li class='$extra_menu_class'>$extra_menu</li>";
		}

		$gitRevision = $this->gitRevision();
		$gitLink = $gitRevision === null ? null : <<<HTML
				<sup> <a href='{$request->requestBaseURI}git.html'>r$gitRevision</a> </sup>
HTML;

		return \template\AuthFilter::filter(

		<<<HTML
			<div class='navbar-header'>
				<span class="navbar-brand">
					<a href='{$request->requestBaseURI}index.html'> {$this->getLogo()} </a>
					$gitLink
				</span>
			</div>
HTML
.

		"<ul class='navbar-nav nav noticebar navbar-collapse'>"
		.$this->component( 'Messages',      'envelope',             'messages',      $this->messages )
		.$this->component( 'Notifications', 'bell',                 'notifications', $this->notifications )
		.$this->component( 'Alerts',        'exclamation-triangle', 'alerts',        $this->alerts )
		."</ul>"
		.$this->getExtraMenus()
		."<ul class='nav navbar-nav noticebar navbar-right'>"
		.		$this->getRightNavContent()
		."	<li><a href='{$request->requestBaseURI}auth.html?action:auth:logout' title='Logout'><i class='fa fa-sign-out'></i></a></li>
      </ul>
		"
		."<div>
				<button type='button' class='navbar-toggle' data-toggle='collapse' data-target='#nav-collapse-1'>
					<span class='sr-only'>Toggle Navigation</span>
					<span class='icon-bar'></span>
				</button>
			</div>"
		);
	}


	private function component( $label, $icon, $key, $list )
	{
		$f = "filter_$key";
		$seendata = array( "seen-$key" => $this->$f() );
		$numseen = count( array_filter( $list, function($v){return true == $v['seen'];} ) );
		$total = count( $list ); 
		$unseen = $total - $numseen;

		return $this->nbmenuitem( $key, $label,
			$unseen ?  "<span class='badge badge-primary'>$unseen</span>" : ''
			,
			$icon,
			empty( $list ) ? "No $label" : $total . " $label(s) ($numseen seen, $unseen unseen)",
			<<<HTML
				<span class='btn btn-default' onclick='
					console.log( this );

					var el = this;

					$.ajax({
						url: "/api/noticebar",
						type: "POST",
						async: true,
						cache: false,
						data:
HTML
			.json_encode( $seendata )
			.<<<HTML
						,
						success: function(response, status, jqxhr) {
							console.log("ajax response: ", response );
							$(el).closest(".dropdown").dropdown("toggle").find(".noticebar-badge" ).html("");// TODO: ani class, fadeout/riseup, forwards
						}
				});


				'>seen</span>
HTML
			,
			$list
		);
	}




	function nbmenuitem( $type, $label, $extra, $icon, $title = null, $body = null, $list = array() )
	{
		$list = implode("", array_map(function($v)
		{
			$time = isset( $v['time'] ) ? "<span class='noticebar-item-time'><i class='fa fa-clock-o'></i> ".$v['time']."</span>" : "";
			list( $a_pre, $a_post ) = isset( $v['link'] ) && !empty( $v['link'] )
			? [ "<a href='{$v['link']}'>", "</a>" ]
			: [ null, null ];

			$ret = <<<HTML
				<li class='noticebar-item'>
HTML;
			if ( isset( $v['img'] ) )
			$ret .= <<<HTML
					<span class='noticebar-item-image'><img src='{$v['img']}'></span>
HTML;
			$ret .= <<<HTML
					<span class='noticebar-item-body'>
						$a_pre <strong class='noticebar-item-title'>{$v['title']}</strong> $a_post
						<span class='noticebar-item-text'>{$v['body']}</span>
						$time
					</span>
				</li>
HTML;
			return $ret;
		}, $list) );

		$subheading = "";

		if ( $title || $body )
		{
			$subheading = <<<HTML
				<li class='noticebar-empty'>
					<h4 class='noticebar-empty-title'>$title</h4>
					<p class='noticebar-empty-text'>$body</p>
				</li>
HTML;
		}


		return <<<HTML
				<!-- alerts -->
				<li class='dropdown noticebar-component-$type'>
					<a class='dropdown-toggle' data-toggle='dropdown' href='javascript:;'>
						<i class='fa fa-$icon'></i>
						<span class='navbar-visible-collapsed'>$label</span>
						<span class='noticebar-badge'>$extra</span>
					</a>
					<ul class='dropdown-menu noticebar-menu noticebar-hoverable' role='menu'>
						<li class='nav-header'><div class='pull-left'><i class='fa fa-$icon'></i> $label</div></li>
						$subheading
						$list
					</ul>
				</li>
HTML;
	}



	/**
	 * Returns the git revision number: counting all commits in the current branch.
	 * Override this with 'return null' to disable.
	 */
	protected function gitRevision()
	{
		return exec( "git log -"."-oneline | wc -l");
	}


	/**
	 */
	protected function getLogo() {
		return null;
		/* example:
		return <<<HTML
			<img alt="Site Title" src='/img/logo-transparent.png' class='navbar-brand-img' height='50'/>
HTML;
		*/
	}


	/**
	 * override to return an <ul class='nav navbar-nav noticebar'>.
	 */
	protected function getExtraMenus()
	{
		return null;
		/* Example:
		global $request;
		return "
			<ul class='nav navbar-nav noticebar' data-auth-role='admin'>
				<li class='divider'><span>|</span></li>
				<li><a href='{$request->requestBaseURI}settings.html' title='Settings'><i class='fa fa-cogs'></i></a></li>
		  </ul>
		";
		*/
	}

	/**
	 * Override to return "<li/>".
	 */
	protected function getRightNavContent()
	{
		return null;
	}
}

