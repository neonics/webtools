/**
 * @author Kenney Westerhof <kenney@neonics.com>
 */

(function()
{
CKEDITOR.plugins.add( 'xmllayout',
	{
		requires : [ 'htmlwriter', 'fakeobjects' ],

		beforeInit : function(editor)
		{
		},
		init : function( editor )
		{
			editor.addCss(
				'div.cke_paypal {' +
					'border-bottom: 2px solid red;'+
					'margin-bottom: 10px;'+
				'}'
			);

			var dataFilterRules =
			{
				elements:
				{
					$ : function (element )
					{
					//	alert("DF: Element: " + element.name);
					},

					paypal: function( element )
					{
						var atts=element.attributes;

						var fake = editor.createFakeParserElement( element, "cke_paypal", "img" );

						fake.attributes[ 'alt' ] = "Paypal";
						fake.attributes[ 'src' ] = 'img/paypal-buynow.png';//CKEDITOR.getUrl('../draft/img/paypal-buynow.png');

						return fake;
					},

					'l:title' : function( element )
					{
						return editor.createFakeParserElement2( element, "", "h2" );
					},

					'l:section' : function( element )
					{
						var s = editor.createFakeParserElement2( element, "", "div" );
						var d = "";
						for ( var i in s.attributes )
							d+= i + "=>" + s.attributes[i] +"\n";
							alert(d);

						var h2= new CKEDITOR.htmlParser.element( "h2" );
						h2.add( new CKEDITOR.htmlParser.text( element.attributes["title"] ) );
						s.children.unshift( h2 );

						s.attributes["title"] = element.attributes["title"];
						s.attributes['data-cke-allowed-attributes'] =
							'title';

						return s;
					},

					'l:link' : function ( element )
					{
						return editor.createFakeParserElement2( element, "", "a" );
					},

					title : function( element )
					{
						return editor.createFakeParserElement2( element, "", "h2" );
					},

					columns : function( element )
					{
						var table = editor.createFakeParserElement2( element, "", "table" );
						table.add( new CKEDITOR.htmlParser.element( "tr" ) );
						return table;
					},

					column : function( element )
					{
						var fake = editor.createFakeParserElement2( element, "", "td" );

						for ( var i in element.attributes )
						{
							fake.attributes[i] = element.attributes[i];
						}

						fake.attributes['data-cke-allowed-attributes'] =
							'width,style';

						return fake;
					}

				}
			};

			var htmlFilterRules =
			{
				elements:
				{
					$ : function( element )
					{
						var atts = element.attributes;

						if ( atts[ 'data-cke-realelement2' ] )
						{
							var atz = decodeURIComponent( atts['data-cke-orig-attributes'] );
							var atrs = ( attributes = {} );
							var q = atz.split("<");
							for ( var i in q )
							{
								atrs[ q[i].substring(0, q[i].indexOf('=')) ]
									= q[i].substring( q[i].indexOf('=')+1 );
							}

							var el = new CKEDITOR.htmlParser.element( 
								atts['data-cke-orig-element' ], atrs );
								//'div', atrs );

							// XXX Workaround - 'title' is treated specially!
							// see htmldataprocessor
							if ( el.name == 'title' )
								el.attributes['data-cke-title'] = element.children[0].value;
							else if ( el.name == 'columns' )
							{
								element = element.children[0]; // tbody
								element = element.children[0]; // tr
							}

							var aa = atts['data-cke-allowed-attributes'];
							if ( aa ) 
							{
								var aaa = aa.split(',');
								for ( var i in aaa )
								{
									el.attributes[aaa[i]] = element.attributes[aaa[i]];
									alert( element.name+": set fake: " + element.attributes[aaa[i]] );
								}
							}

							for ( i in element.children )
							{
								el.add( element.children[i] );
							}

							return el;
						}

						return element;
					},

/*
					'l:section' : function(element)
					{
						alert( "FOO section: " + element.attributes["title"] );
						return element;
					},
*/
					'h2' : function( element )
					{
						//alert( "FOO " + element );
						if ( element.parent.name == 'l:section' )
						{
						element.parent.attributes["title"] = element.children[0].value;

						alert( element.parent.name + ": " + element.parent.attributes["title"]);
						 element.children.shift();
						delete element.name;
alert( "FR: " + htmlFilterRules );
						htmlFilterRules.elements.$( element.parent );

						return null;
						}
						else
							return element;
					}
				}
			};



			var dataProcessor = editor.dataProcessor,
				htmlFilter = dataProcessor && dataProcessor.htmlFilter,
				dataFilter = dataProcessor && dataProcessor.dataFilter;

			if ( htmlFilter )
				htmlFilter.addRules( htmlFilterRules );
			
			if ( dataFilter )
			{
				dataFilter.addRules( dataFilterRules );
			}
			else
			{
				alert("No datafilter!");
			}
		}
	}
);


// XXX TODO
// add context menu or perhaps edit in place the xml:lang attribute...
// encode in fake element, allow that to be edited and then on conversion
// back, replace the attribute...

CKEDITOR.editor.prototype.createFakeParserElement2 = function( realElement, className, realElementType, isResizable )
{
	var lang = this.lang.fakeobjects,
		label = lang[ realElementType ] || lang.unknown,
		html;

	var writer = new CKEDITOR.htmlParser.basicWriter();
	realElement.writeHtml( writer );
	html = writer.getHtml();

	var atz="";
	for ( var i in realElement.attributes )
	{
		atz += (atz.length==0?"":"<") + i+'='+realElement.attributes[i];
	}

	var attributes =
	{
		'class' : className,
		src : CKEDITOR.getUrl( 'images/spacer.gif' ),
		'data-cke-realelement2' : encodeURIComponent( html ),
		'data-cke-real-node-type' : realElement.type,
		'data-cke-orig-element' : realElement.name,
		'data-cke-orig-attributes' : encodeURIComponent( atz ),
		alt : label,
		title : label,
		align : realElement.attributes.align || ''
	};

	if ( realElementType )
		attributes[ 'data-cke-real-element-type' ] = realElementType;

	if ( isResizable )
		attributes[ 'data-cke-resizable' ] = isResizable;

	var el= new CKEDITOR.htmlParser.element( realElementType, attributes );
	for ( c in realElement.children )
		el.add( realElement.children[ c] );
	return el;
};


//CKEDITOR.xmllayout = (function() {
//});


})();
