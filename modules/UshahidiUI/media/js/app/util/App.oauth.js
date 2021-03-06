define(['backbone', 'jso2', 'jquery'],
	function(Backbone, jso2, $) {
		
		jso2.enablejQuery($);
		
		var oauth = new jso2('ushahidi', {
			// @todo change client_id and ensure it always exists
			client_id: window.config.oauth.client,
			authorization: window.config.baseurl + "oauth/authorize",
			redirect_uri: window.config.baseurl,
			scopes: {
				request: ["posts", "forms", "api"],
				require: ["posts"]
			}
		});
		
		oauth.callback();
		
		Backbone.ajax = _.bind(oauth.ajax, oauth);
		
		return oauth;
	}); 