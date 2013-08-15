define(['marionette', 'handlebars', 'App', 'leaflet', 'util/App.oauth', 'text!templates/Map.html'],
	function(Marionette, Handlebars, App, L, OAuth, template) {
		// Hack to fix default image url
		L.Icon.Default.imagePath = App.config.baseurl + "media/kohana/images";
		
		return Marionette.ItemView.extend(
		{
			// Blank template just so it renders
			template : Handlebars.compile(template),
			initialize: function(options) {},
			// Use onDomRefresh rather than render() because we need this.$el in the DOM first
			onDomRefresh: function()
			{
				// Don't re-render the map
				if (typeof this.map != "undefined") return this;
				
				// add an OpenStreetMap tile layer
				var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
					attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
				});
				
				var cloudmadeUrl = 'http://{s}.tile.cloudmade.com/528babad266546698317425055510f96/{styleId}/256/{z}/{x}/{y}.png',
					cloudmadeAttribution = 'Map data &copy; 2011 OpenStreetMap contributors, Imagery &copy; 2011 CloudMade';
				
				var minimal   = L.tileLayer(cloudmadeUrl, {styleId: 22677, attribution: cloudmadeAttribution});
				
				// create a map in the "map" div, set the view to a given place and zoom
				var map = this.map = L.map(this.$('#map')[0], {
					center : new L.LatLng(-36.85, 174.78),
					zoom : 5,
					layers : [minimal],
					scrollWheelZoom : false
				});
				
				posts = L.geoJson().addTo(this.map);
				OAuth.ajax({
					url : App.config.baseurl + 'api/v2/posts/geojson',
					success: function (data) {
						// If geojson was empty, return
						if (data.features.length == 0) return;

						posts.addData(data);

						// Center map on post markers
						//map.fitBounds(posts.getBounds()).setZoom(5);
						map.panTo(posts.getBounds().getCenter(), { animate: false });
					}
				});
				
				var baseMaps = {
					"Minimal": minimal
				};
				
				var overlayMaps = {
					"Posts": posts
				};
				
				L.control.layers(baseMaps, overlayMaps).addTo(this.map);
				
				return this;
			},
			events : {
				'click .js-collapse-map' : 'collapseMap'
			},
			collapseMap : function () {
				$('#map').toggleClass('map-collapse');
				this.$('.js-collapse-tab').toggleClass('none');
				this.$('.js-expand-tab').toggleClass('none');
				this.$('.leaflet-container .leaflet-control-zoom').toggle();
				return false;
			}
		});
	}); 