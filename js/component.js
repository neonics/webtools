/**
 * Dynamic Component library
 *
 * Usage:
 *
 * declare
 *
 *   	data-component='COMPONENT-NAME'
 *
 * on any tag. On page loads,
 *
 *   	/api/component/COMPONENT-NAME
 *
 * is requested and the resulting html content appended to the
 * element declaring the data-component attribute.
 *
 * Initialisation parameters can be declared using
 *
 *  	data-COMPONENT-NAME-param="VALUE"
 *
 * and will be sent as HTTP POST parameters during component retrieval. 
 *
 * For slow-loading components,
 *
 *  	data-component-loading-icon='CSS_CLASSES'
 *
 * can be declared, which will append 
 *
 *   <i class='tmp-component-loading CSS_CLASSES'></i>
 *
 * to the element, to be removed upon completion.
 * An example of CSS_CLASSES using font-awesome icons is 'fa fa-spinner fa-spin'.
 *
 * @author Kenney Westerhof <kenney@neonics.com>
 */
!function ($) {

  "use strict"; // jshint ;_;

  var Component = function (element, options) {
		// note: this doesn't seem to be called.
    this.options = $.extend({}, $.fn.component.defaults, options)
    this.$window = $(window)
    this.$element = $(element)
		console.log("component constructor", element, options);
  }


  var old = $.fn.component

  $.fn.component = function (option) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('component')
        , options = typeof option == 'object' && option

      if (!data) $this.data('component', (data = new Component(this, options)))
      if (typeof option == 'string') data[option]();
			console.log("data=", data, "option=", option);

			var component_args = {};
			var tmp0 = "data-" + data + '-';
			for ( var i=0; i < this.attributes.length; i ++ )
			{
				if ( this.attributes[i].name.slice( 0, tmp0.length ) == tmp0 )
				{
					var paramname = this.attributes[i].name.substr( tmp0.length );
					var paramvalue= this.attributes[i].value;
					component_args[ paramname ] = paramvalue;
				}
			}

			var spinner=null;
			var icon_classes = options.spinner || $this.data( 'component-loading-icon');
			if ( icon_classes )
			{
				// font-awesome spinner icons: spinner, circle-o-notch, cog/gear, refresh
				$this.append( "<i class='tmp-component-loading " + icon_classes + "'></i>" );
				spinner = $this.find( '.tmp-component-loading' );
			}

			$.ajax({
				type:"POST",//GET
				url:"/api/component/" + data,
				//accepts:
				//async:true
				//cache: (def true for dataType 'script' and 'jsonp')
				//contents:
				//contentType:

				//dataType: xml|html|script|json|jsonp|text
				//global:true (ajaxStart/Stop)
				//headers:{http-headers}
				//statusCode:{404:function(){}}
				error:function(jqXHR,status,error){
					console.log("error",status,error,arguments)
					if ( spinner != null ) spinner.remove();
					var a = $this.append( "<div class='alert alert-danger'>" + error + "<br>"+jqXHR.responseText+"</div>" )
				},
				success:function(data,status,jqXHR){
					//console.log("success","$this=",$this,"this=",this,"status=",status,"data=",data); 
					if ( spinner != null ) spinner.remove();
					var a = $this.append( data )

					a.find( '[data-component]' ).each(function () {
						var $nested = $(this)
							, data = $nested.data()

						console.log("nested component el=", $nested, "data=",data);

						$nested.component(data)
					} );

				},
				complete:function(jqXHR, status){console.log("complete",status)},
				//timeout:millisecs,
				data: $.param( component_args )
			})
			//'Promise' callbacks:
			//.done(function(){console.log("done",this,arguments)})
			//.fail(function(){console.log("fail",this,arguments)})
			//.always(function(){console.log("always",this,arguments)})
			//.then(function(){console.log("then",this,arguments)})
			;

    })
  }

  $.fn.component.Constructor = Component
  $.fn.component.defaults = {}

  $.fn.component.noConflict = function () {
    $.fn.component = old;
    return this;
  }


  $(window).on('load', function () {
    $('[data-component]').each(function () {
      var $this = $(this);
      $this.component( $this.data() )
    })
  })

}(window.jQuery);
