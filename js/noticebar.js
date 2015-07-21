!function($) {
	"use strict"; // jshint ;_;

	var NoticeBar = function (element, options) {
		console.log("construct NoticeBar", element, options);
		this.options = $.extend({}, $.fn.noticebar.defaults, options)
		this.$window = $(window)
//      .on('scroll.affix.data-api', $.proxy(this.checkPosition, this))
 //     .on('click.affix.data-api',  $.proxy(function () { setTimeout($.proxy(this.checkPosition, this), 1) }, this))
		this.$element = $(element)

		var _t = this;

		this.notice = function( kind, title, message, badge_count ) {

			var a = $('.noticebar-component-' + kind );
			a.find( '.noticebar-empty' ).remove();
			var b = a.find('.noticebar-badge');
			b.html( "<span class='badge badge-primary'>"+ ( parseInt( b.text() || 0 ) + parseInt(badge_count||1) )+"</span>" );

			a.find( '.noticebar-menu' ).append(
			"<li class='noticebar-item'>"
			+ "<span class='noticebar-item-body'>"
			+ "  <strong class='noticebar-item-title'>"+ title +"</strong>"
			+ "  <span class='noticebar-item-text'>"+ message+"</span>"
			+ "</span>"
			+"</li>"
			);
		}

		this.alert = function( title, message, badge_count ) {
			_t.notice( 'alerts', title, message, badge_count );
		}
		this.notification = function( title, message, badge_count ) {
			_t.notice( 'notifications', title, message, badge_count );
		}
	}

 //NoticeBar.prototype.checkPosition = function () {
 //   if (!this.$element.is(':visible')) return;
 //}
	var old = $.fn.noticebar

	$.fn.noticebar = function (option) {
		//return this.each(function () {
			var $this = $(this)
				, data = $this.data('noticebar')
				, options = typeof option == 'object' && option

			if (!data) $this.data('noticebar', (data = new NoticeBar(this, options)))

			var args = []; for ( var i in arguments ) args.push( arguments[i] );
			if (typeof option == 'string') data[option].apply( this, args.slice( 1 ) )
		//})
	}

	$.fn.noticebar.noConflict = function () {
		$.fn.noticebar = old
		return this
	}

	$.fn.noticebar.Constructor = NoticeBar

	$.fn.noticebar.defaults = {
	}


	$(window).on('load', function () {
		console.log("noticebar load");
		InitNoticeBar();
	})

////////

	var _startX = 0; // mouse starting positions
	var _startY = 0;
	var _offsetX = 0; // current element offset
	var _offsetY = 0; 
	var _dragElement; // needs to be passed from OnMouseDown to OnMouseMove 
	var _oldZIndex = 0; // we temporarily increase the z-index during drag 
	var _debug = $('debug'); // makes life easier

	// this is simply a shortcut for the eyes and fingers
	//function $(id) { return document.getElementById(id); }

	//InitNoticeBar();
	function InitNoticeBar() {
	//	 document.onmousedown = OnMouseDown;
	//	 document.onmouseup = OnMouseUp;

		$('[data-noticebar]').each(function () {
			var $this = $(this)
			, data = $this.data()

			console.log("noticebar el=", $this, "data=",data);

			$this.noticebar(data)
		})

	}

}(window.jQuery);
